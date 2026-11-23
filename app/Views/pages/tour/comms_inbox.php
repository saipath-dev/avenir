<?= view('layouts/head') ?>
<style>
    /* ---------- Unmatched incoming e-mails (tf-comms-r3, 27 Oct 2026) — same tokens as Communications & History ---------- */
    .tfc { --tf-navy: #1e3553; --tf-navy-2: #2c4a73; --tf-purple: #842484; --tf-purple-soft: #f3e3f3; --tf-green: #1f8a4c; --tf-green-soft: #e4f4ea; --tf-avgreen: #78b848; --tf-amber: #1a1a1a; --tf-amber-soft: #ffffff; --tf-red: #b23a32; --tf-red-soft: #fbe9e7; --tf-blue-soft: #e6eef8; --tf-ink: #1d2733; --tf-muted: #5f6f80; --tf-line: #dfe5ec; --tf-bg: #f4f6f9; --tf-card: #fff; --tf-radius: 10px; color: var(--tf-ink); }
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
    .tfc .pill-purple { background: var(--tf-purple-soft); color: var(--tf-purple); } .tfc .pill-green { background: var(--tf-green-soft); color: var(--tf-green); } .tfc .pill-amber { background: var(--tf-amber-soft); border:1px solid #d9d3dd; color: var(--tf-amber); } .tfc .pill-red { background: var(--tf-red-soft); color: var(--tf-red); } .tfc .pill-grey { background: #eef1f5; color: var(--tf-muted); } .tfc .pill-blue { background: var(--tf-blue-soft); color: var(--tf-navy); }
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
    .tfc .not-installed { padding: 14px 16px; border-radius: 8px; background: var(--tf-amber-soft); border:1px solid #d9d3dd; color: var(--tf-amber); }
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
        else if (!s.configured) h += `<b>No incoming mailbox is connected.</b> ${SYSADMIN ? 'Connect one under Admin → Settings → Incoming Mailboxes.' : 'Ask the System Administrator to set it under Tour Desk Admin → Settings.'} `;
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
                ${['unmatched', 'new'].includes(r.status) ? `<div class="acts"><button class="btn btn-primary btn-sm" data-attach="${r.id}"><i class="bi bi-link-45deg"></i> Attach to a record…</button><button class="btn btn-avgreen btn-sm" data-newbk="${r.id}"><i class="bi bi-plus-circle"></i> New booking…</button><button class="btn btn-light btn-sm" data-discard="${r.id}"><i class="bi bi-x-circle"></i> Discard…</button><button class="btn btn-light btn-sm" data-open="${r.id}"><i class="bi bi-eye"></i> Full message</button></div>` : `<div class="acts"><button class="btn btn-light btn-sm" data-open="${r.id}"><i class="bi bi-eye"></i> Full message</button></div>`}
            </div>`;
        }).join('') : '<div class="empty">Nothing here.</div>';
        box.querySelectorAll('[data-attach]').forEach(b => b.addEventListener('click', () => openFull(Number(b.dataset.attach), 'attach')));
        box.querySelectorAll('[data-discard]').forEach(b => b.addEventListener('click', () => openFull(Number(b.dataset.discard), 'discard')));
        box.querySelectorAll('[data-newbk]').forEach(b => b.addEventListener('click', () => openFull(Number(b.dataset.newbk), 'new')));
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
    // tf-fullmsg-r1 (20 Nov 2026): Full message — read the e-mail and act on it in the same window (attach, new booking, discard)
    let FM = null;   // { id, m, mode, busy, done, pick }
    function fmClose() { document.getElementById('fm_ov')?.remove(); FM = null; }
    async function openFull(id, mode) {
        const j = await api('/message/' + id); if (j.status !== 200) { toast('error', j.message); return; }
        const row = S.rows.find(x => Number(x.id) === id) || {};
        FM = { id, m: j.data, mode: mode || '', busy: false, done: null, pick: null, row };
        document.getElementById('fm_ov')?.remove();
        const ov = document.createElement('div'); ov.id = 'fm_ov'; ov.innerHTML = `<style>
            #fm_ov{position:fixed;inset:0;background:rgba(30,10,30,.55);z-index:1055;display:flex;align-items:center;justify-content:center;padding:14px}
            #fm_ov .fm{background:#fff;border-radius:12px;width:min(980px,100%);max-height:94vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 12px 40px rgba(0,0,0,.35);font-size:13.5px;text-align:left}
            #fm_ov .fm-h{background:#842484;color:#fff;padding:11px 16px;display:flex;justify-content:space-between;align-items:center;font-weight:700;font-size:16px}
            #fm_ov .fm-b{overflow:auto;padding:14px 16px;flex:1;min-height:120px}
            #fm_ov .fm-meta{background:#f7f3f8;border-radius:8px;padding:8px 12px;margin-bottom:10px;display:grid;grid-template-columns:70px 1fr;gap:2px 8px}
            #fm_ov .fm-meta b{color:#5a5160} #fm_ov pre{white-space:pre-wrap;font:inherit;margin:0 0 12px}
            #fm_ov .fm-att{display:flex;align-items:center;gap:10px;border:1px solid #e3dce7;border-radius:8px;padding:7px 10px;margin-top:6px}
            #fm_ov .fm-f{border-top:2px solid #842484;background:#fbf8fc;padding:10px 16px;max-height:48vh;overflow:auto}
            #fm_ov .fm-tabs{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
            #fm_ov .fm-tab{border:1.5px solid #cfc6d3;background:#fff;border-radius:8px;padding:8px 10px;font-weight:700;cursor:pointer}
            #fm_ov .fm-tab.on{background:#842484;color:#fff;border-color:#842484} #fm_ov .fm-tab.g.on{background:#78b848;border-color:#78b848}
            #fm_ov .fm-tab:disabled{opacity:.5;cursor:not-allowed}
            #fm_ov .fm-p{border:1.5px solid #842484;border-radius:8px;background:#fff;padding:10px 12px;margin-top:10px}
            #fm_ov .fm-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:8px} @media(max-width:800px){#fm_ov .fm-cards,#fm_ov .fm-tabs{grid-template-columns:1fr 1fr}}
            #fm_ov .fm-card{border:1px solid #cfc6d3;border-radius:8px;padding:9px 10px;background:#fff;cursor:pointer;text-align:left} #fm_ov .fm-card:hover{border-color:#78b848} #fm_ov .fm-card b{display:block;margin-bottom:3px} #fm_ov .fm-card span{font-size:12px;color:#5a5160}
            #fm_ov .fm-card:disabled{opacity:.5;cursor:not-allowed}
            #fm_ov .fm-row{padding:6px 8px;border-bottom:1px solid #eee;cursor:pointer} #fm_ov .fm-row.on{background:#f3e3f3}
            #fm_ov .fm-go{background:#78b848;color:#fff;font-weight:700;border:0;border-radius:8px;padding:8px 16px} #fm_ov .fm-go:disabled{opacity:.5}
            #fm_ov .fm-ok{background:#e8f3de;border:1px solid #78b848;border-radius:8px;padding:10px 12px} #fm_ov .fm-err{color:#b3261e;font-weight:700;margin-top:6px}
        </style><div class="fm"><div class="fm-h"><span><i class="bi bi-envelope"></i> <span id="fm_t"></span></span><button class="btn btn-sm btn-light" id="fm_x">Close</button></div><div class="fm-b" id="fm_body"></div><div class="fm-f" id="fm_foot"></div></div>`;
        document.body.appendChild(ov);
        const m = FM.m, kb = n => n > 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB';
        document.getElementById('fm_t').textContent = m.subject || '(no subject)';
        document.getElementById('fm_x').addEventListener('click', fmClose);
        document.getElementById('fm_body').innerHTML = `<div class="fm-meta"><b>From:</b><span>${esc(m.from_addr || '')}</span><b>To:</b><span>${esc(m.to_addr || '')}${m.cc_addr ? ' · cc ' + esc(m.cc_addr) : ''}</span><b>Date:</b><span>${dt(m.received_at)}</span><b>Mailbox:</b><span>${m.box === 'hotel' ? 'Hotel reservations' : (m.box === 'agent' ? 'Agent inquiries' : '—')}</span></div>
            <pre>${esc(m.body_text || '(no text in the message)')}</pre>
            <div><b><i class="bi bi-paperclip"></i> Attachments (${(m.attachments || []).length})</b>${(m.attachments || []).map(a => `<div class="fm-att"><i class="bi bi-file-earmark"></i><span style="flex:1"><b>${esc(a.original_name)}</b> <span class="help">${esc(a.mime || '')} · ${kb(Number(a.bytes || 0))}</span></span>${/\.(pdf|png|jpe?g|gif|webp|txt)$/i.test(a.original_name || '') ? `<a class="btn btn-sm btn-light" style="border:1px solid #78b848;color:#3f7a1c" target="_blank" href="${BASE}/attachment/${a.id}"><i class="bi bi-box-arrow-up-right"></i> Open</a>` : ''}<a class="btn btn-sm" style="background:#78b848;color:#fff" href="${BASE}/attachment/${a.id}?dl=1"><i class="bi bi-download"></i> Download</a></div>`).join('') || '<div class="help">None.</div>'}</div>`;
        fmFoot();
    }
    function fmFoot() {
        const f = document.getElementById('fm_foot'); if (!f || !FM) return; const m = FM.m, hotel = m.box === 'hotel';
        if (FM.done) { f.innerHTML = `<div class="fm-ok">${FM.done}</div>`; return; }
        if (!['unmatched', 'new'].includes(m.status)) {
            f.innerHTML = `<div class="help">${m.status === 'matched' ? 'This e-mail is filed on ' + (FM.row.inquiry_no ? `<a href="${COMMS}/${FM.row.intake_id}"><b>${esc(FM.row.inquiry_no)}${FM.row.tour_no ? ' · ' + esc(FM.row.tour_no) : ''}</b></a>` : 'a record') + '.' : 'This e-mail was ' + esc(m.status) + (m.note ? ': ' + esc(m.note) : '') + '.'}</div>`; return; }
        const tab = (k, label, icon, cls) => `<button class="fm-tab ${cls || ''} ${FM.mode === k ? 'on' : ''}" data-fm="${k}" ${FM.busy ? 'disabled' : ''}><i class="bi ${icon}"></i> ${label}</button>`;
        let p = '';
        if (FM.mode === 'attach' || FM.mode === 'ongoing') p = `<div class="fm-p"><b style="color:#842484"><i class="bi bi-link-45deg"></i> ${FM.mode === 'ongoing' ? 'Ongoing communication — find the inquiry or confirmed tour' : 'Find inquiry or confirmed tour'}</b>
            <input class="form-control form-control-sm mt-2" id="fm_q" placeholder="Inquiry or tour number, client, tour name or agent" autocomplete="off"><div id="fm_rows" style="max-height:150px;overflow:auto;margin-top:6px"></div>
            <div style="display:flex;gap:8px;align-items:center;margin-top:8px"><b style="white-space:nowrap">Reason for linking</b><input class="form-control form-control-sm" id="fm_reason" maxlength="200" value="${FM.mode === 'ongoing' ? 'Ongoing communication on this record' : ''}" placeholder="e.g. agent follow-up for this inquiry"></div>
            <div style="display:flex;gap:12px;align-items:center;margin-top:8px"><button class="fm-go" id="fm_attach" disabled><i class="bi bi-paperclip"></i> Attach email and files</button><span class="help"><i>Keeps the existing record number. Creates no new inquiry.</i></span></div><div class="fm-err" id="fm_err"></div></div>`;
        if (FM.mode === 'new') p = `<div class="fm-p" style="border-color:#78b848"><b style="color:#3f7a1c">New booking options — these create a NEW inquiry</b>${hotel ? '<div class="fm-err">This e-mail came through the Hotel reservations mailbox: a hotel e-mail cannot create an inquiry. Use Attach to a record.</div>' : ''}
            <div class="fm-cards mt-2"><button class="fm-card" data-nb="auto" ${hotel ? 'disabled' : ''}><b><i class="bi bi-file-earmark-plus"></i> Auto-create new inquiry</b><span>Read email, create INQ number, link original email and files. Staff check details.</span></button>
            <button class="fm-card" data-nb="paste" ${hotel ? 'disabled' : ''}><b><i class="bi bi-envelope"></i> Paste the mail</b><span>Open Paste Email Inquiry with this email filled in.</span></button>
            <button class="fm-card" data-nb="manual" ${hotel ? 'disabled' : ''}><b><i class="bi bi-pencil"></i> Do it by hand</b><span>Open manual form; link this email and files when saved.</span></button>
            <button class="fm-card" data-nb="ongoing"><b><i class="bi bi-chat-dots"></i> Ongoing communication</b><span>Link to an existing inquiry or confirmed tour.</span></button></div>
            <div class="help mt-2"><i class="bi bi-info-circle"></i> Creating an inquiry does not confirm a booking.</div><div class="fm-err" id="fm_err"></div></div>`;
        if (FM.mode === 'discard') p = `<div class="fm-p" style="border-color:#b3261e"><b style="color:#b3261e">Discard this e-mail from the queue</b><div class="help">It is never deleted: it stays on file and in the mailbox, and the Activity log records who discarded it and why.</div>
            <div style="display:flex;gap:8px;margin-top:8px"><input class="form-control form-control-sm" id="fm_why" maxlength="200" placeholder="Reason (required, at least five characters)"><button class="btn btn-sm btn-danger" id="fm_disc" style="white-space:nowrap">Discard</button></div><div class="fm-err" id="fm_err"></div></div>`;
        f.innerHTML = `<div class="fm-tabs">${tab('attach', 'Attach to a record', 'bi-link-45deg')}${tab('new', 'New booking', 'bi-plus-lg', 'g')}${tab('discard', 'Discard', 'bi-trash')}<button class="fm-tab" data-fm="close">✕ Close</button></div>${FM.mode ? p : '<div class="help mt-2">Read first, then choose an action here.</div>'}`;
        f.querySelectorAll('[data-fm]').forEach(b => b.addEventListener('click', () => { if (b.dataset.fm === 'close') { fmClose(); return; } if (FM.busy) return; FM.mode = b.dataset.fm; FM.pick = null; fmFoot(); }));
        const err = t => { const e = document.getElementById('fm_err'); if (e) e.textContent = t || ''; };
        const busy = (on) => { FM.busy = on; f.querySelectorAll('button').forEach(b => { if (b.dataset.fm !== 'close') b.disabled = on; }); };
        const ok = (html) => { FM.done = html + ' <button class="btn btn-sm btn-light ms-2" onclick="document.getElementById(\'fm_x\').click()">Close</button>'; FM.busy = false; fmFoot(); load(); };
        const q = document.getElementById('fm_q');
        if (q) { let t = null; const find = async () => { const box = document.getElementById('fm_rows'), v = q.value.trim(); FM.pick = null; document.getElementById('fm_attach').disabled = true; if (!v) { box.innerHTML = ''; return; }
                const r = await api('/find?q=' + encodeURIComponent(v)); const rows = (r && r.data) || [];
                box.innerHTML = rows.length ? rows.map(x => `<div class="fm-row" data-pick="${x.id}"><b>${esc(x.tour_no || x.inquiry_no)}</b>${x.tour_no ? ' (' + esc(x.inquiry_no) + ')' : ''} | ${esc(x.tour_name || '')} | ${esc(x.agent_name || '')} | ${x.arrival_date ? String(x.arrival_date).slice(0, 10) : 'no dates'} | <b>${x.tour_no ? 'Confirmed tour' : 'Inquiry'}</b></div>`).join('') : '<div class="help">No inquiry or tour matches.</div>';
                box.querySelectorAll('[data-pick]').forEach(el => el.addEventListener('click', () => { box.querySelectorAll('.fm-row').forEach(y => y.classList.remove('on')); el.classList.add('on'); FM.pick = rows.find(y => String(y.id) === el.dataset.pick); document.getElementById('fm_attach').disabled = false; })); };
            q.addEventListener('input', () => { clearTimeout(t); t = setTimeout(find, 300); });
            const tag = (FM.row.tags || [])[0]; if (tag) { q.value = (String(tag).match(/\d{3,}/g) || [tag]).pop(); find(); } q.focus();
            document.getElementById('fm_attach').addEventListener('click', async () => { if (FM.busy || !FM.pick) return; const reason = document.getElementById('fm_reason').value.trim(); if (reason.length < 5) { err('Enter the reason for linking (at least five characters).'); return; }
                const h = FM.pick; busy(true); let j; try { j = await post(`/${FM.id}/attach`, { intake_id: h.id, reason }); } catch (e) { j = { status: 0, message: 'The server did not answer. Nothing was attached; the e-mail is still in the queue.' }; }
                if (j.status !== 200) { busy(false); err(j.message || 'Not attached. The e-mail is still in the queue.'); return; }
                ok(`<b>Attached to the existing record ${esc(h.inquiry_no)}${h.tour_no ? ' · ' + esc(h.tour_no) : ''}</b> — the e-mail and its ${(FM.m.attachments || []).length} file(s) are on its Communications &amp; History. No new inquiry was created. <a href="${COMMS}/${h.id}"><b>Open the record</b></a>`); }); }
        f.querySelectorAll('[data-nb]').forEach(b => b.addEventListener('click', async () => { if (FM.busy) return; const k = b.dataset.nb;
            if (k === 'ongoing') { FM.mode = 'ongoing'; fmFoot(); return; }
            if (k !== 'auto') { busy(true); location.href = INTAKE + '?start=' + k + '&mail=' + FM.id; return; }
            busy(true); b.querySelector('span').textContent = 'Reading the e-mail and creating the inquiry…'; let j; try { j = await post(`/${FM.id}/draft`, {}); } catch (e) { j = { status: 0, message: 'The server did not answer. Check the Inquiries list before trying again; the e-mail is still in the queue.' }; }
            if (j.status !== 200) { busy(false); fmFoot(); err(j.message || 'No inquiry was created. The e-mail is still in the queue.'); return; }
            const d = j.data || {}; ok(`<b>New inquiry ${esc(d.inquiry_no || '')} created</b> — the original e-mail and its files are on it. Nothing was confirmed or sent; staff must check the details.${(d.blockers || []).length ? '<br>To settle first: ' + d.blockers.map(esc).join('; ') : ''} <a href="${INTAKE}?id=${d.intake_id}"><b>Open ${esc(d.inquiry_no || 'the inquiry')}</b></a>`); }));
        document.getElementById('fm_disc')?.addEventListener('click', async () => { if (FM.busy) return; const why = document.getElementById('fm_why').value.trim(); if (why.length < 5) { err('Give the reason (at least five characters).'); return; }
            busy(true); let j; try { j = await post(`/${FM.id}/discard`, { why }); } catch (e) { j = { status: 0, message: 'The server did not answer. Nothing was discarded.' }; }
            if (j.status !== 200) { busy(false); err(j.message || 'Not discarded.'); return; } ok('<b>Discarded from the queue.</b> The message stays on file and in the mailbox.'); });
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
    // tf-newbk-r1 (20 Nov 2026): a new booking from this e-mail — by hand, by pasting it, or created automatically
    async function newBooking(id) {
        const pick = await Swal.fire({ title: 'New booking from this e-mail', width: 620, showConfirmButton: false, showCloseButton: true,
            html: `<div style="display:grid;gap:10px;text-align:left;font-size:13px">
                <button class="btn btn-avgreen" data-nb="auto" style="text-align:left;padding:10px 14px"><b><i class="bi bi-magic"></i> Auto generate</b><br><span style="font-weight:400">The system reads the e-mail, creates the inquiry number and files this e-mail on it. Staff then check the details.</span></button>
                <button class="btn btn-light" data-nb="paste" style="text-align:left;padding:10px 14px;border:1px solid #cfc6d3"><b><i class="bi bi-envelope"></i> Paste the mail</b><br><span style="font-weight:400">Opens the Paste Email Inquiry screen with this e-mail already filled in, so you can check the text before it is read.</span></button>
                <button class="btn btn-light" data-nb="manual" style="text-align:left;padding:10px 14px;border:1px solid #cfc6d3"><b><i class="bi bi-pencil"></i> Do it by hand</b><br><span style="font-weight:400">Opens the manual inquiry form. When you save it, this e-mail is filed on the new inquiry.</span></button>
                <div style="border:1.5px solid #842484;border-radius:8px;padding:10px 14px"><b style="color:#842484"><i class="bi bi-chat-dots"></i> Ongoing communication</b><br><span>This e-mail belongs to an inquiry or tour that already exists. Type its number and it is filed there.</span>
                    <div style="display:flex;gap:8px;margin-top:8px"><input class="form-control form-control-sm" id="nb_no" placeholder="e.g. INQ 1049 or AVH-2026-00007" autocomplete="off"><button class="btn btn-sm" id="nb_go" style="background:#842484;color:#fff;font-weight:700;white-space:nowrap">File on this number</button></div><div id="nb_msg" class="help" style="margin-top:6px"></div></div></div>`,
            didOpen: el => { el.querySelectorAll('[data-nb]').forEach(b => b.addEventListener('click', () => { Swal.close(); if (b.dataset.nb === 'auto') draft(id); else location.href = INTAKE + '?start=' + b.dataset.nb + '&mail=' + id; }));
                const go = async () => { const msg = el.querySelector('#nb_msg'), raw = el.querySelector('#nb_no').value.trim(); if (!raw) { msg.textContent = 'Type the inquiry or tour number.'; return; }
                    const norm = v => String(v || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
                    const f = await api('/find?q=' + encodeURIComponent((raw.match(/\d{3,}/g) || [raw]).pop())); const hits = ((f && f.data) || []).filter(r => norm(r.inquiry_no) === norm(raw) || norm(r.tour_no) === norm(raw));
                    if (hits.length !== 1) { msg.innerHTML = '<b style="color:#b3261e">' + (hits.length ? 'More than one record has that number.' : 'No inquiry or tour has exactly that number.') + '</b> Check it, or use "Attach to a record…" to search.'; return; }
                    const h = hits[0]; el.querySelector('#nb_go').disabled = true;
                    const j = await post(`/${id}/attach`, { intake_id: h.id, reason: 'Ongoing communication — number ' + raw + ' entered by staff' });
                    if (j.status !== 200) { el.querySelector('#nb_go').disabled = false; msg.innerHTML = '<b style="color:#b3261e">' + esc(j.message || 'Not filed.') + '</b>'; return; }
                    Swal.fire({ icon: 'success', title: 'Filed on ' + esc(h.inquiry_no) + (h.tour_no ? ' · ' + esc(h.tour_no) : ''), text: 'The e-mail is now on that record and its row shows a new e-mail waiting for a reply.', showCancelButton: true, confirmButtonText: 'Open the record', cancelButtonText: 'Stay here' }).then(r => { if (r.isConfirmed) location.href = COMMS + '/' + h.id; });
                    load(); };
                el.querySelector('#nb_go').addEventListener('click', go); el.querySelector('#nb_no').addEventListener('keydown', e => { if (e.key === 'Enter') go(); }); } });
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
