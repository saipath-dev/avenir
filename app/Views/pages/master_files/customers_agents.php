<?= view('layouts/head') ?>
<style>
    /* ---------- Customers & Agents (tf-stage6) — the same tokens as the other master pages ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1a1a1a; --tf-amber-soft:#ffffff;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
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
    .tf .tbl tbody tr:hover { background:var(--tf-bg); }
    .tf .tbl tr.agent td:first-child { border-left:3px solid #842484; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-amber { background:var(--tf-amber-soft); border:1px solid #d9d3dd; color:var(--tf-amber); }
    .tf .pill-purple { background:#f3e6f3; color:#842484; }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:10px 0; }
    .tf .filters { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .tf .tabs { display:flex; gap:6px; flex-wrap:wrap; }
    .tf .tabs button { border:1px solid var(--tf-line); background:#fff; border-radius:20px; padding:4px 12px; font-size:12.5px; color:var(--tf-ink); }
    .tf .tabs button.on { background:#842484; border-color:#842484; color:#fff; }
    .tf .tabs button .n { opacity:.75; margin-left:4px; }
    .tf .sec { font-weight:600; font-size:12px; color:var(--tf-navy); text-transform:uppercase; letter-spacing:.04em; margin:14px 0 6px; }
    .tf .sec:first-child { margin-top:0; }
    .tf dl.kv { display:grid; grid-template-columns:150px 1fr; gap:6px 12px; margin:0; font-size:12.5px; }
    .tf dl.kv dt { color:var(--tf-muted); font-weight:500; } .tf dl.kv dd { margin:0; }
    .tf .num { text-align:right; }
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
                        <h4>Customers &amp; Agents</h4>
                        <div class="sub">The customer ledger as the quotation desk uses it: <b>agents</b> first (the travel companies that send tours), then direct customers. An agent carries its quotation currency and a default markup; the inquiry offers agents first and reads those settings when an agent is matched. The records and codes are the existing ones — a new customer is still added on <a href="<?= base_url($new_url ?? 'master_files/customer') ?>">New customer</a> and the full details are edited on <a href="<?= base_url($edit_url ?? 'master_files/customer/all') ?>">All customers</a>. Marking an agent and its quotation settings <?= !empty($can_set) ? 'are yours to set here (written to the history)' : 'are set by Management (Pricing) or the System Administrator' ?>.</div>
                    </div>
                </div>

                <?php if (empty($installed)): ?>
                <div class="not-installed">The customer ledger has not been upgraded for agents yet. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Tour Inquiry</a> as the administrator and press Install now; this screen then works.</div>
                <?php else: ?>

                <div class="tf-card mb-3">
                    <div class="tf-card-h"><h6>Customers</h6>
                        <div class="filters">
                            <input class="form-control form-control-sm" id="l_q" placeholder="Search a customer or agent: name, code, country, e-mail…" value="<?= esc($initial_q ?? '') ?>">
                            <div class="tabs" id="l_tabs"><button data-f="" class="on">All<span class="n" id="n_all"></span></button><button data-f="agents">Agents<span class="n" id="n_agents"></span></button><button data-f="direct">Direct customers<span class="n" id="n_direct"></span></button></div>
                            <span class="help" id="l_count"></span>
                        </div>
                    </div>
                    <div class="tf-card-b" style="padding:0">
                        <div class="table-responsive"><table class="tbl" id="t_list"><thead><tr><th>Customer</th><th>Code</th><th>Country</th><th>Kind</th><th>Quotes in</th><th>Default markup</th><th class="num">Tours</th><th>Last tour</th><th class="num">Inquiries</th><th></th></tr></thead><tbody></tbody></table></div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade tf tf-skin" id="m_rec" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="r_title">Customer</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="r_body"></div>
    <div class="modal-footer"><a class="btn btn-light btn-sm" id="r_edit" href="#" target="_blank">Edit full details</a><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'master_files/customer/agents') ?>';
    const SITE = '<?= base_url() ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const CAN_SET = <?= !empty($can_set) ? 'true' : 'false' ?>;
    const OPEN_ACC = <?= json_encode((string) ($open_acc ?? '')) ?>;
    const EDIT_URL = '<?= base_url($edit_url ?? 'master_files/customer/all') ?>';
    const INTAKE_URL = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2600 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const $ = id => document.getElementById(id);
    const fmtDate = d => d ? new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—';
    let F = <?= json_encode((string) ($initial_f ?? '')) ?>, T = null, CUR = null;

    async function load() {
        const q = $('l_q').value.trim();
        const j = await api('/data?q=' + encodeURIComponent(q) + '&f=' + encodeURIComponent(F));
        if (j.status !== 200) { toast('error', j.message); return; }
        const c = j.counts || {}; $('n_all').textContent = c.all ? '(' + c.all + ')' : ''; $('n_agents').textContent = '(' + (c.agents || 0) + ')'; $('n_direct').textContent = '(' + (c.direct || 0) + ')';
        const rows = j.data || [];
        $('l_count').textContent = rows.length + ' shown';
        document.querySelector('#t_list tbody').innerHTML = rows.map(r => `<tr class="${r.is_agent ? 'agent' : ''}" data-acc="${esc(r.acc)}" style="cursor:pointer">
            <td><b>${esc(r.name)}</b>${r.long_name && r.long_name.trim() !== r.name.trim() ? `<div class="help">${esc(r.long_name)}</div>` : ''}</td>
            <td class="mono">${esc(r.acc)}</td><td>${esc(r.country || '—')}</td>
            <td>${r.is_agent ? '<span class="pill pill-purple">Agent</span>' : '<span class="pill pill-grey">Direct</span>'}</td>
            <td>${r.is_agent ? esc(r.quote_currency || 'company setting') : ''}</td>
            <td>${r.is_agent ? (r.quote_markup_pct != null ? esc(r.quote_markup_pct) + ' %' : 'company default') : ''}</td>
            <td class="num mono">${r.tours || ''}</td><td>${r.tours ? fmtDate(r.last_tour) : ''}</td><td class="num mono">${r.inquiries || ''}</td>
            <td><button class="btn btn-light btn-sm">Open</button></td></tr>`).join('') || '<tr><td colspan="10" class="empty">Nothing matches.</td></tr>';
        document.querySelectorAll('#t_list tbody tr[data-acc]').forEach(tr => tr.addEventListener('click', () => open(tr.dataset.acc)));
    }

    async function open(acc) {
        const j = await api('/record?acc=' + encodeURIComponent(acc));
        if (j.status !== 200) { toast('error', j.message); return; }
        const r = CUR = j.data;
        $('r_title').textContent = r.name + (r.is_agent ? ' — agent' : ' — direct customer');
        $('r_edit').href = EDIT_URL + '?q=' + encodeURIComponent(r.name);
        const settings = CAN_SET ? `
            <div class="d-flex gap-2 flex-wrap align-items-end">
                <div><label class="help">Kind</label><div><button class="btn btn-sm ${r.is_agent ? 'btn-light' : 'btn-primary'}" id="s_agent" data-on="${r.is_agent ? 0 : 1}">${r.is_agent ? 'Make a direct customer' : 'Mark as an agent'}</button></div></div>
                ${r.is_agent ? `<div><label class="help">Quotes in</label><input class="form-control form-control-sm" id="s_cur" maxlength="3" placeholder="company setting" value="${esc(r.quote_currency || '')}" style="width:110px;text-transform:uppercase"></div>
                <div><label class="help">Default markup %</label><input class="form-control form-control-sm" id="s_pct" type="number" step="0.01" min="0" max="100" placeholder="company default" value="${r.quote_markup_pct != null ? r.quote_markup_pct : ''}" style="width:130px"></div>
                <div><button class="btn btn-primary btn-sm" id="s_save">Save settings</button></div>` : ''}
            </div>
            <div class="help mt-1">${r.is_agent ? 'The inquiry reads these when this agent is matched; the markup on each inquiry is still decided under Pricing. Blank = the company setting.' : 'An agent is offered first on the inquiry and carries its quotation currency and default markup.'}</div>`
            : `<div class="help">Kind: <b>${r.is_agent ? 'agent' : 'direct customer'}</b>${r.is_agent ? ' · quotes in ' + esc(r.quote_currency || 'the company setting') + ' · default markup ' + (r.quote_markup_pct != null ? r.quote_markup_pct + ' %' : 'the company default') : ''}. Changed by Management (Pricing) or the System Administrator.</div>`;
        $('r_body').innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="sec">Record</div>
                    <dl class="kv"><dt>Name</dt><dd>${esc(r.name)}</dd><dt>Long name</dt><dd>${esc(r.long_name || '—')}</dd><dt>Code</dt><dd class="mono">${esc(r.acc)}</dd><dt>Prefix</dt><dd>${esc(r.prefix || '—')}</dd><dt>Country</dt><dd>${esc(r.country || '—')}</dd>
                    <dt>E-mail</dt><dd>${esc(r.email || '—')}</dd><dt>Phone</dt><dd>${esc([r.phone, r.mobile].filter(Boolean).join(' · ') || '—')}</dd><dt>Contact</dt><dd>${esc(r.contact_person || '—')}</dd><dt>Address</dt><dd>${esc(r.address || '—')}</dd></dl>
                    <div class="sec">Agent settings</div>${settings}
                    ${r.agent_set_at ? `<div class="help mt-1">Kind last set by ${esc(r.agent_set_by || '')} on ${esc(String(r.agent_set_at).slice(0, 16))}${r.agent_note ? ' — ' + esc(r.agent_note) : ''}</div>` : ''}
                </div>
                <div class="col-md-6">
                    <div class="sec">Recent tours (${r.tours})</div>
                    ${(r.recent_tours || []).length ? `<table class="tbl"><tbody>${r.recent_tours.map(t => `<tr><td class="mono">${esc(t.manual_id || t.tour_id)}</td><td>${esc(t.consignee || '')}</td><td>${fmtDate(t.start_date)}</td><td class="num">${esc(t.passenger_count || '')} pax</td></tr>`).join('')}</tbody></table>` : '<div class="empty">No tours under this customer.</div>'}
                    <div class="sec">Recent inquiries (${r.inquiries})</div>
                    ${(r.recent_inquiries || []).length ? `<table class="tbl"><tbody>${r.recent_inquiries.map(i => `<tr><td><a href="${INTAKE_URL}?id=${i.id}" target="_blank">${esc(i.inquiry_no)}</a></td><td>${esc(i.tour_name || '')}</td><td>${fmtDate(i.arrival_date)}</td><td><span class="pill pill-grey">${esc(i.status)}</span></td></tr>`).join('')}</tbody></table>` : '<div class="empty">No inquiries under this customer.</div>'}
                    <div class="sec">History</div>
                    ${(r.history || []).length ? `<table class="tbl"><tbody>${r.history.map(h => `<tr><td style="white-space:nowrap">${esc(String(h.at).slice(0, 16))}</td><td>${esc(h.by_name || h.by_user || '')}</td><td>${esc(h.summary || h.action)}</td></tr>`).join('')}</tbody></table>` : '<div class="empty">No changes recorded yet.</div>'}
                </div>
            </div>`;
        $('s_agent')?.addEventListener('click', async () => {
            const on = Number($('s_agent').dataset.on) === 1;
            const a = await Swal.fire({ icon: 'question', title: on ? 'Mark ' + r.name + ' as an agent?' : 'Make ' + r.name + ' a direct customer?', input: 'text', inputPlaceholder: 'Reason (kept in the history)', showCancelButton: true, confirmButtonText: on ? 'Mark as agent' : 'Make direct customer' });
            if (!a.isConfirmed) return;
            const j2 = await post('/set_agent', { acc: r.acc, agent: on ? 1 : 0, note: a.value || '' });
            toast(j2.status === 200 ? 'success' : 'error', j2.message); if (j2.status === 200) { await load(); open(r.acc); }
        });
        $('s_save')?.addEventListener('click', async () => {
            const j2 = await post('/set_quote', { acc: r.acc, quote_currency: $('s_cur').value, quote_markup_pct: $('s_pct').value });
            toast(j2.status === 200 ? 'success' : 'error', j2.message); if (j2.status === 200) { await load(); open(r.acc); }
        });
        const el = $('m_rec'); (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(el) : new bootstrap.Modal(el)).show();
        if (window.TfView) TfView.setParams({ acc: r.acc }, { push: !TfView.param('acc') });
    }
    $('m_rec').addEventListener('hidden.bs.modal', () => { if (window.TfView && TfView.param('acc')) TfView.setParams({ acc: null }, { push: true }); });

    $('l_q').addEventListener('input', () => { clearTimeout(T); T = setTimeout(load, 220); });
    document.querySelectorAll('#l_tabs button').forEach(b => b.addEventListener('click', () => { F = b.dataset.f; document.querySelectorAll('#l_tabs button').forEach(x => x.classList.toggle('on', x === b)); load(); }));
    document.querySelectorAll('#l_tabs button').forEach(b => b.classList.toggle('on', b.dataset.f === F));
    load().then(() => { const acc = OPEN_ACC || (window.TfView ? TfView.param('acc') : ''); if (acc) open(acc); });
})();
</script>
</body>
</html>
