<?= view('layouts/head') ?>
<style>
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff; --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:960px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:8px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr.lifted td { opacity:.6; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); } .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); } .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-purple { background:#f3e6f3; color:#842484; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .sec { font-weight:600; font-size:12px; color:var(--tf-navy); text-transform:uppercase; letter-spacing:.04em; margin:14px 0 6px; }
    .tf .sec:first-child { margin-top:0; }
    .tf .warnbox { background:var(--tf-amber-soft); border:1px solid #d9d3dd; border-radius:8px; padding:8px 12px; font-size:12.5px; }
    .tf .redbox { background:var(--tf-red-soft); border-radius:8px; padding:8px 12px; font-size:12.5px; color:var(--tf-red); }
    .tf .pick { border:1px solid var(--tf-line); border-radius:8px; max-height:220px; overflow:auto; }
    .tf .pick div { padding:7px 10px; border-bottom:1px solid var(--tf-line); cursor:pointer; font-size:12.5px; }
    .tf .pick div:hover, .tf .pick div.on { background:#f3e6f3; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; }
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
                        <h4>Blocks &amp; Investigations</h4>
                        <div class="sub">A block is put on one exact record — a supplier (driver, guide, restaurant, jeep operator…), a hotel, a customer or agent, or one bank account — with the reason, a reference and restricted notes, and lifted only here, with a reason. <b>No new work</b> stops the record being put on new work (inquiries, quotations, hotel bookings, allocations, supplier bookings); <b>Payments held</b> stops approval, batching, bulk payment and bank release, and lets quotations through with a warning; <b>Suspended pending investigation</b> stops both. Existing tours, allocations, prices, balances and accounting entries are never changed by a block — they are flagged for review. Super Admin only: nobody else opens this page or overrides a refusal.</div>
                    </div>
                    <?php if (!empty($installed)): ?><button class="btn btn-primary btn-sm" id="b_new"><i class="bi bi-shield-exclamation"></i> Block a record</button><?php endif; ?>
                </div>
                <?php if (empty($installed)): ?>
                <div class="not-installed">The block tables are not installed yet. Open Tour Inquiry as the administrator and press Install now.</div>
                <?php else: ?>
                <div class="tf-card mb-3">
                    <div class="tf-card-h"><h6>Blocks</h6><span class="help" id="l_count"></span></div>
                    <div class="tf-card-b" style="padding:0"><div class="table-responsive"><table class="tbl" id="t_list"><thead><tr><th>Record</th><th>Kind</th><th>Reason · reference</th><th>Since</th><th>By</th><th>Status</th><th></th></tr></thead><tbody></tbody></table></div></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade tf tf-skin" id="m_new" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Block a record</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="sec">1 · The exact record</div>
        <div class="row g-2">
            <div class="col-md-4"><select class="form-select form-select-sm" id="n_type"><?php foreach (($types ?? []) as $k => $v): ?><option value="<?= esc($k) ?>"><?= esc($v) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-8"><input class="form-control form-control-sm" id="n_q" placeholder="Search the master: name, code, account number…" autocomplete="off"></div>
        </div>
        <div class="pick mt-2" id="n_pick" hidden></div>
        <div id="n_chosen" class="help mt-1"></div>
        <div class="sec">2 · Exactly what this block touches</div>
        <div id="n_preview" class="help">Pick a record above.</div>
        <div class="sec">3 · Kind, reason, reference, notes</div>
        <div class="row g-2">
            <div class="col-12"><?php foreach (($kinds ?? []) as $k => $v): ?><label class="me-3" style="font-size:12.5px"><input type="radio" name="n_kind" value="<?= esc($k) ?>" <?= $k === 'payments' ? 'checked' : '' ?>> <?= esc($v) ?></label><?php endforeach; ?></div>
            <div class="col-md-8"><input class="form-control form-control-sm" id="n_reason" maxlength="500" placeholder="Reason — shown on every refusal (at least ten characters)"></div>
            <div class="col-md-4"><input class="form-control form-control-sm" id="n_ref" maxlength="120" placeholder="Reference (invoice, letter, case…)"></div>
            <div class="col-12"><textarea class="form-control form-control-sm" id="n_notes" rows="2" placeholder="Restricted notes — seen only here"></textarea></div>
        </div>
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="n_apply" disabled>Apply block</button></div>
</div></div></div>

<div class="modal fade tf tf-skin" id="m_one" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="o_title">Block</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="o_body"></div>
    <div class="modal-footer" id="o_foot"></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'master_files/blocks') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const KINDS = <?= json_encode($kinds ?? []) ?>, TYPES = <?= json_encode($types ?? []) ?>, OPEN_ID = <?= (int) ($open_id ?? 0) ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const $ = id => document.getElementById(id);
    // tf-stage10 fix (31 Oct 2026): the record window is a Bootstrap modal whose focus trap pulled the keyboard back from the
    // SweetAlert reason box ("Lift this block?"), so nothing could be typed there. The modals open with focus:false; SweetAlert
    // dialogs opened over them focus their own input. Permissions, the ten-character reason and the history are unchanged.
    const show = id => { const el = $(id); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el, { focus: false }) : new bootstrap.Modal(el, { focus: false })).show(); };
    const focusSwalInput = () => { const i = document.querySelector('.swal2-input'); if (i) { setTimeout(() => i.focus(), 50); } };
    const hide = id => { const i = bootstrap.Modal.getInstance($(id)); if (i) i.hide(); };
    const kindPill = k => k === 'work' ? '<span class="pill pill-amber">No new work</span>' : (k === 'payments' ? '<span class="pill pill-red">Payments held</span>' : '<span class="pill pill-red">Suspended</span>');
    const dt = s => esc(String(s || '').slice(0, 16));

    async function load() {
        const j = await api('/data'); const rows = j.data || [];
        const act = rows.filter(r => r.status === 'active').length;
        $('l_count').textContent = act + ' active · ' + (rows.length - act) + ' lifted';
        document.querySelector('#t_list tbody').innerHTML = rows.map(b => `<tr class="${b.status}" data-id="${b.id}" style="cursor:pointer">
            <td><b>${esc(TYPES[b.target_type] || b.target_type)}</b><br>${esc(b.target_label)}</td><td>${kindPill(b.kind)}</td>
            <td>${esc(b.reason)}${b.reference ? '<br><span class="help">ref ' + esc(b.reference) + '</span>' : ''}</td>
            <td style="white-space:nowrap">${dt(b.set_at)}</td><td>${esc(b.set_by_name || b.set_by)}</td>
            <td>${b.status === 'active' ? '<span class="pill pill-red">active</span>' : '<span class="pill pill-grey">lifted ' + dt(b.lifted_at).slice(0, 10) + '</span>'}</td>
            <td><button class="btn btn-light btn-sm">Open</button></td></tr>`).join('') || '<tr><td colspan="7" class="empty">No blocks. Nothing is held.</td></tr>';
        document.querySelectorAll('#t_list tbody tr[data-id]').forEach(tr => tr.addEventListener('click', () => open(Number(tr.dataset.id))));
    }

    async function open(id) {
        const j = await api('/one?id=' + id); if (j.status !== 200) { toast('error', j.message); return; }
        const b = j.data;
        $('o_title').textContent = (TYPES[b.target_type] || '') + ' — ' + b.target_label;
        $('o_body').innerHTML = `<div class="mb-2">${kindPill(b.kind)} ${b.status === 'active' ? '<span class="pill pill-red">active</span>' : '<span class="pill pill-grey">lifted</span>'}</div>
            <div class="sec">Reason</div><div>${esc(b.reason)}</div>${b.reference ? '<div class="help">Reference: ' + esc(b.reference) + '</div>' : ''}
            <div class="help">Applied by ${esc(b.set_by_name || b.set_by)} on ${dt(b.set_at)}${b.lifted_at ? ' · lifted by ' + esc(b.lifted_by_name || b.lifted_by) + ' on ' + dt(b.lifted_at) + ' — ' + esc(b.lift_reason || '') : ''}</div>
            <div class="sec">Restricted notes</div><div>${b.notes ? esc(b.notes).replace(/\n/g, '<br>') : '<span class="help">none</span>'}</div>
            <div class="d-flex gap-2 mt-2"><input class="form-control form-control-sm" id="o_note" placeholder="Add a restricted note…"><button class="btn btn-light btn-sm" id="o_addnote">Add</button></div>
            <div class="sec">History</div>
            <table class="tbl"><tbody>${(b.log || []).map(l => `<tr><td style="white-space:nowrap">${dt(l.at)}</td><td>${esc(l.user_name || l.user)}</td><td><b>${esc(l.action)}</b> ${esc(l.detail || '')}</td></tr>`).join('')}</tbody></table>`;
        $('o_foot').innerHTML = (b.status === 'active' ? '<button class="btn btn-primary btn-sm" id="o_lift">Lift this block</button>' : '') + '<button class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>';
        $('o_addnote').addEventListener('click', async () => { const j2 = await post('/note', { id: b.id, text: $('o_note').value }); toast(j2.status === 200 ? 'success' : 'error', j2.message); if (j2.status === 200) open(b.id); });
        $('o_lift')?.addEventListener('click', async () => {
            const a = await Swal.fire({ icon: 'question', title: 'Lift this block?', text: 'Work and payments with ' + b.target_label + ' resume under the usual checks. Give the reason; it is kept in the history.', input: 'text', inputPlaceholder: 'Reason (at least ten characters)', showCancelButton: true, confirmButtonText: 'Lift block', didOpen: focusSwalInput });
            if (!a.isConfirmed) return;
            const j2 = await post('/lift', { id: b.id, reason: a.value || '' }); toast(j2.status === 200 ? 'success' : 'error', j2.message);
            if (j2.status === 200) { hide('m_one'); load(); }
        });
        show('m_one');
    }

    // ---- block a record
    let CH = null, T = null;
    $('b_new').addEventListener('click', () => { CH = null; $('n_q').value = ''; $('n_pick').hidden = true; $('n_chosen').textContent = ''; $('n_preview').textContent = 'Pick a record above.'; $('n_reason').value = ''; $('n_ref').value = ''; $('n_notes').value = ''; $('n_apply').disabled = true; show('m_new'); });
    async function find() {
        const q = $('n_q').value.trim(); if (q.length < 2) { $('n_pick').hidden = true; return; }
        const j = await api('/find?type=' + encodeURIComponent($('n_type').value) + '&q=' + encodeURIComponent(q));
        const rows = j.data || [];
        $('n_pick').innerHTML = rows.map(r => `<div data-key="${esc(r.key)}">${esc(r.label)}</div>`).join('') || '<div class="help">Nothing matches.</div>';
        $('n_pick').hidden = false;
        $('n_pick').querySelectorAll('div[data-key]').forEach(d => d.addEventListener('click', () => choose(d.dataset.key, d.textContent)));
    }
    $('n_q').addEventListener('input', () => { clearTimeout(T); T = setTimeout(find, 220); });
    $('n_type').addEventListener('change', () => { CH = null; $('n_apply').disabled = true; $('n_chosen').textContent = ''; $('n_preview').textContent = 'Pick a record above.'; find(); });
    async function choose(key, label) {
        CH = { type: $('n_type').value, key }; $('n_pick').hidden = true; $('n_chosen').innerHTML = 'Chosen: <b>' + esc(label) + '</b>';
        $('n_preview').innerHTML = 'Reading…';
        const j = await api('/preview?type=' + encodeURIComponent(CH.type) + '&key=' + encodeURIComponent(key)); const p = j.data || {};
        if (p.error) { $('n_preview').innerHTML = '<div class="redbox">' + esc(p.error) + '</div>'; $('n_apply').disabled = true; return; }
        $('n_preview').innerHTML = `<table class="tbl"><thead><tr><th>Linked record</th><th style="text-align:right">Today</th><th>Effect</th></tr></thead><tbody>${(p.linked || []).map(l => `<tr><td>${esc(l.what)}</td><td style="text-align:right" class="mono">${l.n}</td><td class="help">${esc(l.detail)}</td></tr>`).join('')}</tbody></table>
            ${(p.matches || []).length ? '<div class="sec">Every record carrying this account number</div><table class="tbl"><thead><tr><th>Where</th><th>Bank</th><th>Holder</th><th>Ends</th><th>State</th></tr></thead><tbody>' + p.matches.map(m => `<tr><td>${esc(m.where)}</td><td>${esc(m.bank || '')}${m.same_bank ? '' : ' <span class="pill pill-amber">different spelling</span>'}</td><td>${esc(m.holder || '')}</td><td class="mono">…${esc(m.last4)}</td><td>${esc(m.state)}</td></tr>`).join('') + '</tbody></table>' : ''}
            ${p.warning ? '<div class="warnbox mt-2">' + esc(p.warning) + '</div>' : ''}`;
        $('n_apply').disabled = false;
    }
    $('n_apply').addEventListener('click', async () => {
        if (!CH) return;
        const kind = document.querySelector('input[name=n_kind]:checked').value;
        const a = await Swal.fire({ icon: 'warning', title: 'Apply ' + KINDS[kind] + '?', text: 'Every route refuses from now; only the Super Admin lifts it. The reason is shown on every refusal.', showCancelButton: true, confirmButtonText: 'Apply block' });
        if (!a.isConfirmed) return;
        const j = await post('/apply', { target_type: CH.type, target_key: CH.key, kind, reason: $('n_reason').value, reference: $('n_ref').value, notes: $('n_notes').value });
        toast(j.status === 200 ? 'success' : 'error', j.message);
        if (j.status === 200) { hide('m_new'); load(); }
    });

    load().then(() => { if (OPEN_ID) open(OPEN_ID); });
})();
</script>
</body>
</html>
