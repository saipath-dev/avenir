<?= view('layouts/head') ?>
<style>
    /* ---------- Unmatched incoming e-mails (tf-comms-r3, 27 Oct 2026) — same tokens as Communications & History ---------- */
    .tfc { --tf-navy: #1e3553; --tf-navy-2: #2c4a73; --tf-purple: #842484; --tf-purple-soft: #f3e7f3; --tf-green: #1f8a4c; --tf-green-soft: #e4f4ea; --tf-avgreen: #78b848; --tf-amber: #b7791f; --tf-amber-soft: #fdf3e1; --tf-red: #b23a32; --tf-red-soft: #fbe9e7; --tf-blue-soft: #e6eef8; --tf-ink: #1d2733; --tf-muted: #5f6f80; --tf-line: #dfe5ec; --tf-bg: #f4f6f9; --tf-card: #fff; --tf-radius: 10px; color: var(--tf-ink); }
    .tfc .tf-card { background: var(--tf-card); border: 1px solid var(--tf-line); border-radius: var(--tf-radius); box-shadow: 0 1px 2px rgba(16,24,40,.04); margin-bottom: 14px; }
    .tfc .tf-card-h { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-bottom: 1px solid var(--tf-line); flex-wrap: wrap; }
    .tfc .tf-card-h h6 { margin: 0; font-weight: 600; color: var(--tf-navy); font-size: 14px; }
    .tfc .tf-card-b { padding: 12px 14px; }
    .tfc .title { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 12px; }
    .tfc .title h4 { margin: 0; color: var(--tf-navy); font-weight: 600; }
    .tfc .title .sub { color: var(--tf-muted); font-size: 12.5px; }
    .tfc .toolbar { margin-left: auto; display: flex; flex-wrap: wrap; gap: 6px; }
    .tfc .btn { border-radius: 8px; font-size: 12.5px; padding: 6px 12px; font-weight: 500; white-space: nowrap; }
    .tfc .btn-sm { padding: 4px 9px; font-size: 12px; }
    .tfc .btn-primary { background: var(--tf-purple); border-color: var(--tf-purple); color: #fff; }
    .tfc .btn-primary:hover { background: #6a1d6a; border-color: #6a1d6a; }
    .tfc .btn-avgreen { background: var(--tf-avgreen); border-color: var(--tf-avgreen); color: #fff; }
    .tfc .btn-light { background: #fff; border: 1px solid var(--tf-line); color: var(--tf-ink); }
    .tfc .help { font-size: 12px; color: var(--tf-muted); }
    .tfc .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; line-height: 1.6; white-space: nowrap; }
    .tfc .pill-purple { background: var(--tf-purple-soft); color: var(--tf-purple); } .tfc .pill-green { background: var(--tf-green-soft); color: var(--tf-green); } .tfc .pill-amber { background: var(--tf-amber-soft); color: var(--tf-amber); } .tfc .pill-red { background: var(--tf-red-soft); color: var(--tf-red); } .tfc .pill-grey { background: #eef1f5; color: var(--tf-muted); } .tfc .pill-blue { background: var(--tf-blue-soft); color: var(--tf-navy); }
    .tfc .form-label { font-size: 11.5px; text-transform: uppercase; letter-spacing: .03em; color: var(--tf-muted); margin-bottom: 2px; }
    .tfc .form-control, .tfc .form-select { font-size: 12.5px; border-radius: 8px; }
    .tfc .filters { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .tfc .filters .chip { border: 1px solid var(--tf-line); background: #fff; border-radius: 999px; padding: 3px 10px; font-size: 12px; color: var(--tf-muted); cursor: pointer; }
    .tfc .filters .chip.on { background: var(--tf-purple-soft); color: var(--tf-purple); border-color: var(--tf-purple-soft); font-weight: 600; }
    .tfc .msg { padding: 10px 12px; border-bottom: 1px solid #eef1f5; }
    .tfc .msg:last-child { border-bottom: 0; }
    .tfc .msg .head { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
    .tfc .msg .when { font-family: Consolas, Menlo, monospace; font-size: 11.5px; color: var(--tf-muted); }
    .tfc .msg .subj { font-weight: 600; color: var(--tf-ink); }
    .tfc .msg .addr { font-size: 12px; color: var(--tf-muted); }
    .tfc .msg .prev { font-size: 12.5px; white-space: pre-wrap; margin-top: 4px; color: #3b4857; max-height: 96px; overflow: hidden; }
    .tfc .msg .acts { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
    .tfc .msg .meta { font-size: 11.5px; color: var(--tf-muted); margin-top: 4px; }
    .tfc .ib-status { background: var(--tf-purple-soft); border-radius: 8px; padding: 8px 12px; font-size: 12px; }
    .tfc .ib-status b { color: var(--tf-purple); }
    .tfc .empty { padding: 22px 14px; text-align: center; color: var(--tf-muted); }
    .tfc .not-installed { padding: 14px 16px; border-radius: 8px; background: var(--tf-amber-soft); color: var(--tf-amber); }
    .tfc .mono { font-family: Consolas, Menlo, monospace; font-size: 12px; }
    .tfc .find-row { padding: 6px 8px; border: 1px solid var(--tf-line); border-radius: 8px; margin-top: 4px; cursor: pointer; font-size: 12.5px; }
    .tfc .find-row:hover, .tfc .find-row.on { background: var(--tf-purple-soft); }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tfc" id="tfc">
            <div class="container-fluid">
                <?= view('layouts/tourdesk_bar') ?>
                <?php if (empty($installed)): ?>
                <div class="tf-card"><div class="tf-card-b not-installed"><b><i class="bi bi-exclamation-triangle"></i> Incoming e-mail (schema 47) is not installed yet.</b> <span class="help">Open Tour Inquiry as System Administrator and press Install / upgrade.</span></div></div>
                <?php else: ?>
                <div class="title">
                    <div><h4>Incoming e-mails — review queue</h4><div class="sub">E-mails collected from the TourFlow mailbox that could not be filed with certainty. Nothing is filed by a similar subject or sender: a person attaches each one to its record (the reason is kept) or discards it (the message stays on file). A message that reads like a quotation request can be read into a new inquiry and a draft quotation for staff review.</div></div>
                    <div class="toolbar"><button class="btn btn-avgreen btn-sm" id="check_now"><i class="bi bi-arrow-repeat"></i> Check mailbox now</button><a class="btn btn-light btn-sm" href="<?= base_url($comms_url ?? 'tour/intake/comms') ?>"><i class="bi bi-search"></i> Communications search</a><a class="btn btn-light btn-sm" href="<?= base_url($intake_url ?? 'tour/intake') ?>"><i class="bi bi-inbox"></i> Tour Inquiry</a></div>
                </div>
                <div class="ib-status mb-3" id="ib_status">Loading…</div>
                <div class="tf-card"><div class="tf-card-h"><h6>Messages</h6><span class="help" id="count"></span>
                    <div class="filters ms-auto"><span class="chip on" data-s="unmatched">Waiting for a decision</span><span class="chip" data-s="matched">Filed</span><span class="chip" data-s="discarded">Discarded</span><span class="chip" data-s="skipped">Skipped</span><span class="chip" data-s="all">All</span></div>
                </div><div id="rows"><div class="empty">Loading…</div></div></div>
                <?php endif; ?>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- attach to a record -->
<div class="modal fade tfc" id="m_attach" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Attach this e-mail to a record</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help mb-2" id="at_about"></div>
        <label class="form-label">Find the inquiry / tour — number, client or tour name, agent</label><input class="form-control form-control-sm" id="at_q" placeholder="INQ 1038, AVH-2026-00012, Watanabe, Lion Royal…">
        <div id="at_rows" style="max-height:220px;overflow:auto"></div>
        <label class="form-label mt-2">Why does it belong there?</label><input class="form-control form-control-sm" id="at_reason" placeholder="e.g. the agent's reply about INQ 1038, confirmed on the phone">
        <input type="hidden" id="at_mail"><input type="hidden" id="at_intake">
    </div>
    <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" id="at_ok" disabled>Attach</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($inbox_url ?? 'tour/intake/comms/inbox') ?>';
    const COMMS = '<?= base_url($comms_url ?? 'tour/intake/comms') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const SYSADMIN = <?= !empty($sysadmin) ? 'true' : 'false' ?>;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const showModal = sel => { const el = document.querySelector(sel); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el, { focus: false }) : new bootstrap.Modal(el, { focus: false })).show(); };
    const hideModal = sel => { const el = document.querySelector(sel); const inst = bootstrap.Modal.getInstance ? bootstrap.Modal.getInstance(el) : null; if (inst) inst.hide(); };
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 3200 : undefined, showConfirmButton: icon !== 'success' });
    async function api(path, opts) { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); if (r.status === 401 || r.redirected) { location.reload(); throw new Error('Signed out'); } return r.json(); }
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const pill = (cls, t) => `<span class="pill pill-${cls}">${esc(t)}</span>`;
    const dt = s => s ? String(s).slice(0, 16).replace('T', ' ') : '';
    if (!INSTALLED) return;
    const S = { status: 'unmatched', rows: [], counts: {}, settings: {} };

    function renderStatus() {
        const s = S.settings, c = S.counts;
        let h = '';
        if (!s.imap_ext) h += `<b style="color:var(--tf-red)">The PHP imap extension is not loaded on this server</b> — the mailbox cannot be read. `;
        else if (!s.configured) h += `<b>Incoming mailbox not set up.</b> ${SYSADMIN ? 'Set it under Tour Desk Admin → Settings → Incoming mailbox.' : 'Ask the System Administrator to set it under Tour Desk Admin → Settings.'} `;
        else h += `<b>Mailbox:</b> ${esc(s.email)} on ${esc(s.host)} (${esc(s.folder)}) · last checked ${s.last_check ? dt(s.last_check) : 'never'}${s.last_result ? ' — ' + esc(s.last_result) : ''} · ${s.enabled ? 'collecting is on' : '<b style="color:var(--tf-red)">collecting is switched off</b>'} · automatic quotation drafts ${s.auto_quote ? 'on' : 'off'}. `;
        h += `<br><b>${c.unmatched || 0}</b> waiting for a decision · ${c.matched || 0} filed · ${c.discarded || 0} discarded · ${c.skipped || 0} skipped.`;
        document.getElementById("ib_status").innerHTML = h;
    }
    function renderRows() {
        const box = document.getElementById('rows');
        document.getElementById('count').textContent = `${S.rows.length} message(s)`;
        box.innerHTML = S.rows.length ? S.rows.map(r => {
            const st = { unmatched: ['amber', 'waiting for a decision'], new: ['amber', 'new'], matched: ['green', 'filed'], discarded: ['grey', 'discarded'], skipped: ['red', 'skipped'] }[r.status] || ['grey', r.status];
            return `<div class="msg" data-id="${r.id}">
                <div class="head"><span class="when">${dt(r.received_at)}</span>${pill(st[0], st[1])}${r.has_reply_hdr == 1 ? pill('blue', 'reply headers present') : ''}${(r.tags || []).map(t => pill('purple', 'tag ' + t)).join('')}${r.looks_like_request && r.status !== 'matched' ? pill('green', 'reads like a quotation request') : ''}${r.attachments > 0 ? pill('grey', r.attachments + ' attachment(s)') : ''}${r.inquiry_no ? `<a class="pill pill-green" href="${COMMS}/${r.intake_id}">${esc(r.inquiry_no)}${r.tour_no ? ' · ' + esc(r.tour_no) : ''}</a>` : ''}</div>
                <div class="subj">${esc(r.subject || '(no subject)')}</div><div class="addr">from <b>${esc(r.from_addr || '?')}</b>${r.to_addr ? ' to ' + esc(r.to_addr) : ''}</div>
                ${r.preview ? `<div class="prev">${esc(r.preview)}</div>` : ''}
                ${r.match_reason ? `<div class="meta"><i class="bi bi-check2-circle"></i> ${esc(r.match_reason)}${r.matched_by ? ' · ' + esc(r.matched_by) + ' ' + dt(r.matched_at) : ''}</div>` : ''}${r.note ? `<div class="meta">${esc(r.note)}</div>` : ''}
                ${['unmatched', 'new'].includes(r.status) ? `<div class="acts"><button class="btn btn-primary btn-sm" data-attach="${r.id}"><i class="bi bi-link-45deg"></i> Attach to a record…</button><button class="btn btn-avgreen btn-sm" data-draft="${r.id}"><i class="bi bi-file-earmark-plus"></i> Read as new inquiry &amp; draft quotation</button><button class="btn btn-light btn-sm" data-discard="${r.id}"><i class="bi bi-x-circle"></i> Discard…</button><button class="btn btn-light btn-sm" data-open="${r.id}"><i class="bi bi-eye"></i> Full message</button></div>` : `<div class="acts"><button class="btn btn-light btn-sm" data-open="${r.id}"><i class="bi bi-eye"></i> Full message</button></div>`}
            </div>`;
        }).join('') : '<div class="empty">Nothing here.</div>';
        box.querySelectorAll('[data-attach]').forEach(b => b.addEventListener('click', () => openAttach(Number(b.dataset.attach))));
        box.querySelectorAll('[data-discard]').forEach(b => b.addEventListener('click', () => discard(Number(b.dataset.discard))));
        box.querySelectorAll('[data-draft]').forEach(b => b.addEventListener('click', () => draft(Number(b.dataset.draft))));
        box.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => openFull(Number(b.dataset.open))));
    }
    async function load() {
        const j = await api('/data?status=' + S.status); if (j.status !== 200) { toast('error', j.message); return; }
        S.rows = j.data.rows; S.counts = j.data.counts; S.settings = j.data.settings; renderStatus(); renderRows();
    }
    document.querySelectorAll('.filters .chip').forEach(c => c.addEventListener('click', () => { document.querySelectorAll('.filters .chip').forEach(x => x.classList.remove('on')); c.classList.add('on'); S.status = c.dataset.s; load(); }));
    document.getElementById('check_now').addEventListener('click', async () => {
        const btn = document.getElementById('check_now'); if (btn.disabled) return; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Checking…';
        let j; try { j = await post('/check', {}); } finally { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Check mailbox now'; }
        Swal.fire({ icon: j.status === 200 ? 'success' : 'error', title: j.status === 200 ? 'Mailbox checked' : 'Could not check the mailbox', html: esc(j.message) + ((j.data && j.data.notes) ? '<br><span class="help">' + j.data.notes.map(esc).join('<br>') + '</span>' : '') });
        load();
    });
    async function openFull(id) {
        const j = await api('/message/' + id); if (j.status !== 200) { toast('error', j.message); return; }
        const m = j.data;
        Swal.fire({ title: esc(m.subject || '(no subject)'), width: 820, html: `<div style="text-align:left;font-size:12.5px"><div class="help">from ${esc(m.from_addr)}${m.to_addr ? ' · to ' + esc(m.to_addr) : ''}${m.cc_addr ? ' · cc ' + esc(m.cc_addr) : ''} · ${dt(m.received_at)}</div><div class="help mono">Message-ID ${esc(m.message_id || '—')}${m.in_reply_to ? ' · In-Reply-To ' + esc(m.in_reply_to) : ''}</div>${(m.attachments || []).length ? '<div style="margin:6px 0">' + m.attachments.map(a => `<a href="${BASE}/attachment/${a.id}" target="_blank" class="btn btn-light btn-sm"><i class="bi bi-paperclip"></i> ${esc(a.original_name)}</a> `).join('') + '</div>' : ''}<pre style="white-space:pre-wrap;font-family:inherit;max-height:420px;overflow:auto;background:#f7f5fa;padding:10px;border-radius:8px">${esc(m.body_text || '(no text)')}</pre></div>`, showConfirmButton: true, confirmButtonText: 'Close' });
    }
    function openAttach(id) {
        const r = S.rows.find(x => Number(x.id) === id); if (!r) return;
        document.getElementById('at_mail').value = id; document.getElementById('at_intake').value = ''; document.getElementById('at_ok').disabled = true;
        document.getElementById('at_about').innerHTML = `<b>${esc(r.subject || '(no subject)')}</b> from ${esc(r.from_addr || '')}, ${dt(r.received_at)}`;
        document.getElementById('at_q').value = (r.tags && r.tags[0]) ? r.tags[0] : ''; document.getElementById('at_rows').innerHTML = ''; document.getElementById('at_reason').value = '';
        showModal('#m_attach'); if (document.getElementById('at_q').value) findRecords();
    }
    let findTimer = null;
    async function findRecords() {
        const q = document.getElementById('at_q').value.trim(); const box = document.getElementById('at_rows');
        if (!q) { box.innerHTML = ''; return; }
        const j = await api('/find?q=' + encodeURIComponent(q)); if (j.status !== 200) { box.innerHTML = `<div class="help">${esc(j.message)}</div>`; return; }
        box.innerHTML = j.data.length ? j.data.map(r => `<div class="find-row" data-pick="${r.id}"><b>${esc(r.inquiry_no)}</b>${r.tour_no ? ' · ' + esc(r.tour_no) : ''} — ${esc(r.tour_name || '')} · ${esc(r.agent_name || '')}${r.agent_email ? ' (' + esc(r.agent_email) + ')' : ''} · ${r.arrival_date ? String(r.arrival_date).slice(0, 10) : ''} · ${esc(r.status)}</div>`).join('') : '<div class="help">No inquiry or tour matches.</div>';
        box.querySelectorAll('[data-pick]').forEach(el => el.addEventListener('click', () => { box.querySelectorAll('.find-row').forEach(x => x.classList.remove('on')); el.classList.add('on'); document.getElementById('at_intake').value = el.dataset.pick; document.getElementById('at_ok').disabled = false; }));
    }
    document.getElementById('at_q').addEventListener('input', () => { clearTimeout(findTimer); findTimer = setTimeout(findRecords, 300); });
    document.getElementById('at_ok').addEventListener('click', async () => {
        const reason = document.getElementById('at_reason').value.trim(); if (reason.length < 5) { toast('error', 'Say why this message belongs to that record (at least five characters).'); return; }
        const j = await post(`/${document.getElementById('at_mail').value}/attach`, { intake_id: document.getElementById('at_intake').value, reason });
        toast(j.status === 200 ? 'success' : 'error', j.message); if (j.status === 200) { hideModal('#m_attach'); load(); }
    });
    async function discard(id) {
        const r = await Swal.fire({ icon: 'warning', title: 'Discard this e-mail from the queue?', text: 'It is never deleted: it stays on file here and in the mailbox, and the Activity log says who discarded it and why.', input: 'text', inputPlaceholder: 'reason (required)', inputValidator: v => (String(v || '').trim().length < 5 ? 'Give the reason (at least five characters).' : undefined), showCancelButton: true, confirmButtonText: 'Discard', confirmButtonColor: '#b23a32' });
        if (!r.isConfirmed) return;
        const j = await post(`/${id}/discard`, { why: r.value }); toast(j.status === 200 ? 'success' : 'error', j.message); load();
    }
    async function draft(id) {
        const ok = await Swal.fire({ icon: 'question', title: 'Read this e-mail as a new inquiry?', html: 'The system reads the dates, passengers, rooms, meals, itinerary and requests, creates the inquiry, files this e-mail on it and — where every rate is on file — costs a <b>draft</b> quotation marked <b>Draft – Staff Review Required</b>.<br><span class="help">Nothing is issued or sent. A person must review and press Issue before it can be e-mailed.</span>', showCancelButton: true, confirmButtonText: 'Read and draft', confirmButtonColor: '#78b848' });
        if (!ok.isConfirmed) return;
        Swal.fire({ title: 'Reading the e-mail…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const j = await post(`/${id}/draft`, {});
        if (j.status !== 200) { Swal.fire({ icon: 'error', title: 'Not done', text: j.message }); load(); return; }
        const d = j.data;
        Swal.fire({ icon: 'success', title: esc(d.inquiry_no) + ' created', html: `<div style="text-align:left;font-size:12.5px">${esc(j.message)}${(d.blockers || []).length ? '<br><b>To settle first:</b><ul>' + d.blockers.map(b => '<li>' + esc(b) + '</li>').join('') + '</ul>' : ''}${(d.flags || []).length ? '<b>Flags:</b><ul>' + d.flags.map(f => '<li>' + esc(f) + '</li>').join('') + '</ul>' : ''}</div>`, showCancelButton: true, confirmButtonText: 'Open the inquiry', cancelButtonText: 'Stay here' }).then(r => { if (r.isConfirmed) location.href = `${INTAKE}?id=${d.intake_id}`; });
        load();
    }
    load();
})();
</script>
</body>
</html>
