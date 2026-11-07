<?= view('layouts/head') ?>
<style>
    /* tf-stage10 Staff Assignment — the approved interface: orchid header, Administration card, eyebrow + title, two buttons, stat cards, four tabs */
    .sa { --orchid:#842484; --orchid-deep:#6d1d6d; --orchid-soft:#f3e6f3; --green:#78b848; --green-dark:#5d9634; --green-soft:#eef6e6; --amber:#9a6a14; --amber-soft:#fcf1dc; --red:#b23a32; --red-soft:#fbe9e7; --ink:#22202a; --muted:#6b6577; --line:#e6e0e8; --bg:#f6f4f7; --card:#fff; color:var(--ink); font-size:14px; }
    .sa .top { background:var(--orchid); color:#fff; border-radius:14px; padding:14px 18px; display:flex; align-items:center; gap:14px; margin-bottom:16px; }
    .sa .top .av { width:40px; height:40px; border-radius:50%; background:#fff; color:var(--orchid); font-weight:700; display:grid; place-items:center; font-size:18px; flex:none; }
    .sa .top .brand { font-family:Georgia, "Source Serif 4", serif; font-size:20px; font-weight:700; }
    .sa .top .srch { margin-left:auto; flex:1 1 180px; max-width:420px; background:#fff; color:#8a8392; border-radius:12px; padding:8px 14px; display:flex; gap:8px; align-items:center; }
    .sa .top .srch input { border:0; outline:0; width:100%; font:inherit; color:var(--ink); background:transparent; }
    .sa .card2 { background:var(--card); border:1px solid var(--line); border-radius:14px; }
    .sa .admin { display:flex; align-items:center; gap:16px; padding:14px 18px; margin-bottom:18px; }
    .sa .admin .gear { width:48px; height:48px; border-radius:50%; background:var(--orchid); color:#fff; display:grid; place-items:center; font-size:22px; flex:none; }
    .sa .admin h4 { margin:0; font-family:Georgia, serif; font-weight:700; } .sa .admin p { margin:2px 0 0; color:var(--muted); }
    .sa .eyebrow { font-size:12px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--orchid); }
    .sa h2.ttl { font-family:Georgia, serif; font-weight:700; font-size:30px; margin:2px 0 6px; }
    .sa .lead { color:var(--muted); max-width:66ch; margin:0 0 14px; font-size:15px; }
    .sa .actions { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px; max-width:620px; }
    .sa .btn2 { font:inherit; font-weight:700; font-size:15px; border-radius:12px; padding:12px 16px; border:1px solid var(--line); background:var(--card); color:var(--ink); text-align:center; cursor:pointer; text-decoration:none; display:block; }
    .sa .btn2.g { background:var(--green); border-color:var(--green); color:#fff; }
    .sa .btn3 { font:inherit; font-weight:600; font-size:12.5px; border-radius:10px; padding:5px 11px; border:1px solid var(--line); background:var(--card); color:var(--ink); cursor:pointer; }
    .sa .btn3.g { background:var(--green); border-color:var(--green); color:#fff; } .sa .btn3.r { color:var(--red); border-color:var(--red); } .sa .btn3.o { background:var(--orchid); border-color:var(--orchid); color:#fff; }
    .sa .banner { background:var(--orchid-soft); border-radius:14px; padding:12px 16px; display:flex; gap:12px; align-items:flex-start; margin-bottom:16px; font-size:14.5px; } .sa .banner b { color:var(--orchid-deep); }
    .sa .stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:20px; }
    .sa .stat { padding:14px 16px; display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
    .sa .stat .l { color:var(--muted); font-size:13px; } .sa .stat .n { font-family:Georgia, serif; font-size:30px; line-height:1.1; margin-top:4px; font-variant-numeric:tabular-nums; }
    .sa .pill { display:inline-block; font-size:11.5px; font-weight:700; border-radius:20px; padding:3px 10px; white-space:nowrap; }
    .sa .p-grey { background:#ebe8ec; color:var(--muted); } .sa .p-orchid { background:var(--orchid-soft); color:var(--orchid); } .sa .p-green { background:var(--green-soft); color:var(--green-dark); } .sa .p-amber { background:var(--amber-soft); color:var(--amber); } .sa .p-red { background:var(--red-soft); color:var(--red); }
    .sa .tabs { display:flex; gap:4px; border-bottom:2px solid var(--line); margin-bottom:14px; overflow-x:auto; }
    .sa .tab { font:inherit; font-weight:700; font-size:15px; color:var(--muted); background:none; border:0; border-bottom:4px solid transparent; margin-bottom:-2px; padding:9px 14px; cursor:pointer; white-space:nowrap; }
    .sa .tab[aria-selected="true"] { color:var(--orchid); border-bottom-color:var(--orchid); }
    .sa .panel { margin-bottom:18px; overflow:hidden; }
    .sa .panel-h { background:var(--orchid); color:#fff; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; }
    .sa .panel-h h5 { margin:0; font-family:Georgia, serif; font-weight:700; font-size:19px; } .sa .panel-h p { margin:2px 0 0; font-size:13px; opacity:.9; }
    .sa .panel-h .pill { background:#fff; color:var(--orchid); }
    .sa .filters { padding:12px 14px; display:grid; gap:10px; grid-template-columns:1fr; border-bottom:1px solid var(--line); }
    @media (min-width:820px) { .sa .filters { grid-template-columns:2fr 1fr 1fr 1fr; } }
    .sa .inp { font:inherit; font-size:14px; border:1.5px solid var(--line); border-radius:12px; padding:9px 12px; background:var(--card); color:var(--ink); width:100%; }
    .sa .tw { overflow-x:auto; }
    .sa table.t { width:100%; border-collapse:collapse; font-size:13.5px; }
    .sa table.t th { text-align:left; font-size:11.5px; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); padding:10px 12px; border-bottom:1px solid var(--line); background:var(--bg); white-space:nowrap; }
    .sa table.t td { padding:10px 12px; border-bottom:1px solid var(--line); vertical-align:top; }
    .sa table.t tr.grp td { background:var(--orchid-soft); color:var(--orchid); font-size:11.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:8px 12px; }
    .sa .gh { display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; }
    .sa .sw { display:inline-flex; border:1px solid var(--line); border-radius:20px; overflow:hidden; font-size:12px; font-weight:700; cursor:pointer; background:var(--card); user-select:none; }
    .sa .sw span { padding:4px 11px; color:var(--muted); } .sa .sw.open span.o { background:var(--green); color:#fff; } .sa .sw.blocked span.b { background:var(--red); color:#fff; }
    .sa .sw:focus-visible { outline:3px solid var(--orchid); outline-offset:2px; }
    .sa .who { color:var(--muted); font-style:italic; } .sa .dept b { display:block; } .sa .dept span { color:var(--muted); font-size:13px; }
    .sa .muted { color:var(--muted); } .sa .small { font-size:12.5px; } .sa code { font-size:12px; background:var(--orchid-soft); padding:1px 5px; border-radius:4px; color:var(--ink); }
    .sa .flow { padding:16px 18px; } .sa .flow h5 { font-family:Georgia, serif; font-weight:700; margin:0; font-size:20px; } .sa .flow .sub { color:var(--muted); margin:2px 0 10px; }
    .sa .step { display:grid; grid-template-columns:44px 1fr; gap:12px; padding:12px 0; border-top:1px solid var(--line); align-items:start; }
    .sa .step .n { width:40px; height:40px; border-radius:50%; background:var(--orchid-soft); color:var(--orchid); font-weight:700; display:grid; place-items:center; }
    .sa .step b { display:block; font-size:15.5px; } .sa .step span { color:var(--muted); }
    .sa .protect { border-left:5px solid var(--green); background:var(--bg); border-radius:10px; padding:12px 14px; margin-top:12px; } .sa .protect b { display:block; margin-bottom:2px; }
    .sa .warnbox { background:var(--amber-soft); border-radius:10px; padding:10px 14px; font-size:13.5px; } .sa .redbox { background:var(--red-soft); color:var(--red); border-radius:10px; padding:10px 14px; font-size:13.5px; }
    .sa .foot { text-align:center; color:var(--muted); font-size:13px; margin-top:14px; }
    .sa .field { display:grid; grid-template-columns:140px 1fr; gap:6px 12px; font-size:13.5px; align-items:center; margin-bottom:8px; } .sa .field > span:first-child { color:var(--muted); }
    @media (max-width:720px) { .sa .field { grid-template-columns:1fr; } .sa .actions { grid-template-columns:1fr; } }
    .sa .tplgrid { display:grid; grid-template-columns:repeat(auto-fit, minmax(270px,1fr)); gap:12px; padding:14px; }
    .sa .tpl { border:1px solid var(--line); border-radius:12px; padding:14px; } .sa .tpl h6 { color:var(--orchid); font-weight:700; font-size:16px; } .sa .tpl ul { padding-left:18px; margin:6px 0 10px; font-size:13px; }
    .modal.sa .modal-header { background:#842484; color:#fff; } .modal.sa .btn-close { filter:invert(1); }
</style>
<?= view('layouts/tf_master_skin') ?>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content"><div class="page-content tf tf-skin" id="tf"><div class="container-fluid sa">
                <?= view('layouts/tourdesk_bar') ?>

<div class="top"><div class="av">A</div><div class="brand">Avenir Holidays</div><div class="srch">&#9906;<input id="pageSearch" placeholder="Search pages…" aria-label="Search pages or actions"></div></div>

<div class="card2 admin"><div class="gear">&#9881;</div><div><h4>Administration</h4><p>accounts, roles and system access</p></div></div>

<div class="eyebrow">Super Admin only</div>
<h2 class="ttl">Staff Assignment</h2>
<p class="lead">Create each position, assign the person you select, and grant only the permissions needed for that responsibility. Every change asks for a reason and is written to the history; nothing is inherited from a group.</p>

<?php if (empty($installed)): ?>
<div class="warnbox">The Staff Assignment tables are not installed yet (schema 57). Run the installer (Tour Inquiry → Setup → Install) as the System Administrator; nothing else changes.</div>
<?php else: ?>

<div class="actions"><a class="btn2" href="<?= base_url($page_url . '/export') ?>" id="b_export">Export access list</a><button class="btn2 g" id="b_new" type="button">+ New staff assignment</button></div>

<div class="banner"><span>&#128274;</span><div><b>Listing is not authorisation.</b> Every account is shown for review; nothing changes for an account until the Super Admin brings it onto the panel and opens rows one by one, with a reason. No account or password is created here.</div></div>

<div class="stats">
  <div class="card2 stat"><div><div class="l">Staff on the panel</div><div class="n" id="st_panel">–</div></div><span class="pill p-grey" id="st_panel_p">Not started</span></div>
  <div class="card2 stat"><div><div class="l">Accounts in the system</div><div class="n" id="st_all">–</div></div><span class="pill p-orchid">Review only</span></div>
  <div class="card2 stat"><div><div class="l">Super Administrators</div><div class="n" id="st_sa">–</div></div><span class="pill p-green">Protected</span></div>
  <div class="card2 stat"><div><div class="l">Payment roles held on the panel</div><div class="n" id="st_roles">–</div></div><span class="pill p-amber">One per person</span></div>
</div>

<div class="tabs" role="tablist">
  <button class="tab" role="tab" data-tab="pos" aria-selected="true" type="button">Positions</button>
  <button class="tab" role="tab" data-tab="accounts" aria-selected="false" type="button">Accounts &amp; assignments</button>
  <button class="tab" role="tab" data-tab="perm" type="button">Permission panel</button>
  <button class="tab" role="tab" data-tab="tpl" type="button">Role templates</button>
  <button class="tab" role="tab" data-tab="hist" type="button">Access &amp; activity history</button>
</div>

<!-- Positions (tf-positions-r1): the position first, then the person who holds it. A position grants nothing. -->
<section id="v_pos">
  <div class="card2 panel">
    <div class="panel-h"><div><h5>Positions</h5><p>each position, and the person who holds it &mdash; a position grants nothing; permissions are opened on the person</p></div><button class="btn2 g" id="b_newpos" type="button">+ New position</button></div>
    <?php if (empty($positions_installed)): ?>
    <div class="warnbox" style="margin:14px">The positions tables are not installed yet. Run the installer (Tour Inquiry &rarr; Setup &rarr; Install) as the System Administrator.</div>
    <?php endif; ?>
    <div class="tw"><table class="t"><thead><tr><th>Position &rarr; username</th><th>Department</th><th>Held since</th><th>Name</th><th>Access</th><th></th></tr></thead><tbody id="pos_body"></tbody></table></div>
  </div>
</section>

<!-- Accounts -->
<section id="v_accounts" hidden>
  <div class="card2 panel">
    <div class="panel-h"><div><h5>Accounts</h5><p id="acc_sub">every account the system has, for review</p></div><span class="pill" id="acc_pill">–</span></div>
    <div class="filters">
      <input class="inp" id="a_q" type="search" placeholder="Search name, sign-in or group">
      <select class="inp" id="a_group"><option value="">All groups</option></select>
      <select class="inp" id="a_state"><option value="">All</option><option value="panel">On the panel</option><option value="eligible">Not on the panel (eligible)</option><option value="enabled">Enabled</option><option value="disabled">Disabled</option><option value="driver">Driver portal sign-ins</option></select>
      <select class="inp" id="a_sort"><option value="group">Sort: group</option><option value="name">Sort: name</option><option value="last">Sort: last activity</option></select>
    </div>
    <div class="tw"><table class="t"><thead><tr><th>Person</th><th>Sign-in</th><th>Group today</th><th>Status</th><th>Last recorded activity</th><th>Panel</th><th></th></tr></thead><tbody id="acc_body"></tbody></table></div>
  </div>
  <div class="card2 flow">
    <h5>Safe assignment flow</h5><p class="sub">One controlled decision at a time</p>
    <div class="step"><div class="n">1</div><div><b>Select the person</b><span>An existing account, brought onto the panel by the Super Admin with a reason. Nothing is created here.</span></div></div>
    <div class="step"><div class="n">2</div><div><b>Choose the department</b><span>A label for the list and the reports; it grants nothing.</span></div></div>
    <div class="step"><div class="n">3</div><div><b>Set responsibilities</b><span>Write exactly what the person is expected to do.</span></div></div>
    <div class="step"><div class="n">4</div><div><b>Review exact permissions</b><span>Every page and action starts Blocked. Open one row at a time, each with its own reason; templates only show what they would open.</span></div></div>
    <div class="step"><div class="n">5</div><div><b>Approve and activate</b><span>Only the Super Admin completes the assignment. Suspension ends the person's sessions at once.</span></div></div>
    <div class="protect"><b>Super Admin protection</b>Nobody can grant Super Admin access from this panel. The System Administrator account cannot be put on it, and Staff Assignment and Blocks &amp; Investigations have no switch for anyone.</div>
  </div>
</section>

<!-- Permission panel -->
<section id="v_perm" hidden>
  <div class="card2 panel">
    <div class="panel-h"><div><h5>Permission panel</h5><p id="p_sub">choose a staff member on the panel</p></div><span class="pill" id="p_count">–</span></div>
    <div class="filters" style="grid-template-columns:2fr 2fr 1fr">
      <select class="inp" id="p_staff"><option value="">— staff member on the panel —</option></select>
      <input class="inp" id="p_q" type="search" placeholder="Search a page or action">
      <select class="inp" id="p_filter"><option value="">All rows</option><option value="open">Open only</option><option value="blocked">Blocked only</option><option value="rights">Action rights only</option></select>
    </div>
    <div id="p_head" class="small muted" style="padding:10px 14px;border-bottom:1px solid var(--line)" hidden></div>
    <div class="tw"><table class="t" id="p_table"><thead><tr><th>Page / action</th><th>URL</th><th>Control</th><th>Last change</th></tr></thead><tbody id="p_body"><tr><td colspan="4" class="muted" style="padding:16px">Choose a staff member above.</td></tr></tbody></table></div>
    <div class="small muted" style="padding:10px 14px">One switch per page or action from the system's own menu table. Rows that share one URL switch together. Payment roles A, B and C are never opened by a group shortcut and refuse each other in any order. Settings, Staff Assignment, Blocks &amp; Investigations and the System-Administrator-only rights have no switch.</div>
  </div>
</section>

<!-- Role templates -->
<section id="v_tpl" hidden>
  <div class="card2 panel">
    <div class="panel-h"><div><h5>Role templates</h5><p>a suggestion list only: it shows exactly which rows it would open; nothing opens until you confirm, each row logged on its own</p></div><span class="pill" id="tpl_pill">–</span></div>
    <div class="tplgrid" id="tpl_grid"></div>
    <div class="small muted" style="padding:0 14px 14px">No template contains a payment role, a Settings page, Blocks &amp; Investigations or Staff Assignment. A payment role is opened only on its own, with its own reason.</div>
  </div>
</section>

<!-- History -->
<section id="v_hist" hidden>
  <div class="card2 panel">
    <div class="panel-h"><div><h5>Access &amp; activity history</h5><p>every change, refusal and status change — appended, never edited</p></div><span class="pill" id="h_pill">–</span></div>
    <div class="filters" style="grid-template-columns:2fr 1fr"><input class="inp" id="h_q" type="search" placeholder="Search person, page, reason"><select class="inp" id="h_kind"><option value="">All entries</option><option value="refused">Refusals</option><option value="opened">Opened</option><option value="blocked">Blocked</option><option value="status">Status changes</option></select></div>
    <div class="tw"><table class="t"><thead><tr><th>When</th><th>By</th><th>Staff member</th><th>Action</th><th>Page / action</th><th>From → to</th><th>Reason</th></tr></thead><tbody id="h_body"></tbody></table></div>
  </div>
</section>

<p class="foot">Avenir Holidays · Staff Assignment · Super Admin only · every change needs a reason and is kept in the history</p>
<?php endif; ?>

    </div></div></div>
</div>
<?= view('layouts/inpage_footer') ?>

<!-- reason dialog -->
<div class="modal fade sa" id="m_reason" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title" id="r_title">Change a permission</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div id="r_body"></div>
        <label for="r_reason" class="small muted" style="display:block;margin:8px 0 4px">Reason (required, at least <?= (int) ($reason_min ?? 5) ?> characters)</label>
        <textarea class="form-control" id="r_reason" rows="2" placeholder="e.g. Joins the Quotation Desk from 1 Nov"></textarea>
        <div class="redbox" id="r_err" hidden style="margin-top:8px"></div>
    </div>
    <div class="modal-footer"><button class="btn3" data-bs-dismiss="modal">Cancel</button><button class="btn3 g" id="r_ok">Confirm</button></div>
</div></div></div>

<!-- add to panel -->
<div class="modal fade sa" id="m_add" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h6 class="modal-title">Bring an account onto the panel</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="field"><span>Account</span><span id="ad_who"></span></div>
        <div class="field"><span>Department label</span><select class="inp" id="ad_dept"><option value="">— none —</option><?php foreach (($departments ?? []) as $d): ?><option><?= esc($d) ?></option><?php endforeach; ?></select></div>
        <div class="field"><span>Responsibilities</span><input class="inp" id="ad_resp" maxlength="500" placeholder="what this person is expected to do"></div>
        <div class="field"><span>Reason</span><input class="inp" id="ad_reason" maxlength="500" placeholder="required"></div>
        <div class="warnbox">From the moment of confirming, this account's group list no longer counts: every page and action is Blocked until opened here. The department label grants nothing.</div>
        <div class="redbox" id="ad_err" hidden style="margin-top:8px"></div>
    </div>
    <div class="modal-footer"><button class="btn3" data-bs-dismiss="modal">Cancel</button><button class="btn3 g" id="ad_ok">Bring onto the panel</button></div>
</div></div></div>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'master_files/staff') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const ROLE_NAMES = <?= json_encode($role_names ?? []) ?>, REASON_MIN = <?= (int) ($reason_min ?? 5) ?>, OPEN_STAFF = <?= (int) ($open_staff ?? 0) ?>;
    if (!INSTALLED) return;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const $ = id => document.getElementById(id);
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2800 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With': 'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const show = id => (bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance($(id)) : new bootstrap.Modal($(id))).show();
    const hide = id => { const i = bootstrap.Modal.getInstance($(id)); if (i) i.hide(); };
    const dt = s => esc(String(s || '').slice(0, 16)) || '—';

    let ACCOUNTS = [], MENUS = [], STAFF = null, PERMS = {}, HIST = [];

    // ---------------------------------------------------------------- tabs
    const views = { pos: 'v_pos', accounts: 'v_accounts', perm: 'v_perm', tpl: 'v_tpl', hist: 'v_hist' };
    function tab(k) { if (!views[k]) k = 'accounts'; document.querySelectorAll('.sa .tab').forEach(t => t.setAttribute('aria-selected', t.dataset.tab === k ? 'true' : 'false')); Object.keys(views).forEach(v => $(views[v]).hidden = v !== k); if (k === 'hist') loadHistory(); }
    document.querySelectorAll('.sa .tab').forEach(t => t.addEventListener('click', () => tab(t.dataset.tab)));

    // ---------------------------------------------------------------- accounts
    async function loadAccounts() {
        const j = await api('/accounts'); ACCOUNTS = j.accounts || [];
        const groups = [...new Set(ACCOUNTS.map(a => a.group))].sort();
        $('a_group').innerHTML = '<option value="">All groups</option>' + groups.map(g => '<option>' + esc(g) + '</option>').join('');
        const onPanel = ACCOUNTS.filter(a => a.on_panel);
        $('st_panel').textContent = onPanel.length; $('st_panel_p').textContent = onPanel.length ? 'On the panel' : 'Not started'; $('st_panel_p').className = 'pill ' + (onPanel.length ? 'p-green' : 'p-grey');
        $('st_all').textContent = ACCOUNTS.length; $('st_sa').textContent = ACCOUNTS.filter(a => a.is_admin).length;
        $('st_roles').textContent = onPanel.reduce((n, a) => n + (a.roles || []).length, 0);
        $('acc_pill').textContent = ACCOUNTS.length + ' accounts · ' + ACCOUNTS.filter(a => a.enabled).length + ' enabled · ' + onPanel.length + ' on the panel';
        $('p_staff').innerHTML = '<option value="">— staff member on the panel —</option>' + onPanel.map(a => '<option value="' + a.staff_id + '">' + esc((a.name || a.username) + ' (' + a.username + ')') + '</option>').join('');
        renderAccounts();
    }
    function renderAccounts() {
        const q = $('a_q').value.trim().toLowerCase(), g = $('a_group').value, st = $('a_state').value, sort = $('a_sort').value;
        let rows = ACCOUNTS.filter(a => (!q || ((a.name || '') + ' ' + a.username + ' ' + a.group).toLowerCase().includes(q)) && (!g || a.group === g));
        if (st === 'panel') rows = rows.filter(a => a.on_panel); else if (st === 'eligible') rows = rows.filter(a => !a.on_panel && !a.is_admin && !a.is_driver && a.enabled);
        else if (st === 'enabled') rows = rows.filter(a => a.enabled); else if (st === 'disabled') rows = rows.filter(a => !a.enabled); else if (st === 'driver') rows = rows.filter(a => a.is_driver);
        if (sort === 'name') rows.sort((x, y) => (x.name || x.username).localeCompare(y.name || y.username)); else if (sort === 'last') rows.sort((x, y) => String(y.last_activity || '').localeCompare(String(x.last_activity || '')));
        $('acc_body').innerHTML = rows.map(a => {
            const status = !a.enabled ? '<span class="pill p-red">Disabled</span>' : (a.on_panel ? (a.status === 'active' ? '<span class="pill p-green">Active</span>' : (a.status === 'suspended' ? '<span class="pill p-amber">Suspended</span>' : '<span class="pill p-red">Deactivated</span>')) : '<span class="pill p-green">Enabled</span>');
            const panel = a.is_admin ? '<span class="pill p-green">Protected</span>' : (a.on_panel ? '<span class="pill p-orchid">On panel · ' + a.open_count + ' open' + ((a.roles || []).length ? ' · role ' + a.roles.join('') : '') + '</span>' : (a.is_driver ? '<span class="pill p-grey">Driver portal · decision 9</span>' : '<span class="pill p-grey">Group list</span>'));
            const act = a.is_admin ? '' : (a.on_panel ? '<button class="btn3 o" data-open="' + a.staff_id + '">Open panel</button>' : ((a.is_driver || !a.enabled) ? '' : '<button class="btn3" data-add="' + a.user_id + '">Bring onto panel</button>'));
            return '<tr><td>' + (a.name ? esc(a.name) : '<span class="who">no employee name</span>') + (a.department ? '<div class="small muted">' + esc(a.department) + '</div>' : '') + '</td><td><code>' + esc(a.username) + '</code></td><td>' + esc(a.group) + '</td><td>' + status + '</td><td class="small">' + (a.last_activity ? dt(a.last_activity) : '<span class="who">none recorded</span>') + '</td><td>' + panel + '</td><td>' + act + '</td></tr>';
        }).join('') || '<tr><td colspan="7" class="muted" style="padding:14px">No account matches.</td></tr>';
    }
    ['a_q', 'a_group', 'a_state', 'a_sort'].forEach(id => { $(id).addEventListener('input', renderAccounts); $(id).addEventListener('change', renderAccounts); });
    $('acc_body').addEventListener('click', e => {
        const add = e.target.closest('[data-add]'), open = e.target.closest('[data-open]');
        if (add) { const a = ACCOUNTS.find(x => x.user_id == add.dataset.add); $('ad_who').innerHTML = '<b>' + esc(a.name || a.username) + '</b> · <code>' + esc(a.username) + '</code> · ' + esc(a.group); $('ad_ok').dataset.user = a.user_id; $('ad_reason').value = ''; $('ad_resp').value = ''; $('ad_err').hidden = true; show('m_add'); }
        if (open) { $('p_staff').value = open.dataset.open; tab('perm'); loadStaff(+open.dataset.open); }
    });
    $('ad_ok').addEventListener('click', async () => {
        const j = await post('/add', { user_id: +$('ad_ok').dataset.user, department: $('ad_dept').value, responsibilities: $('ad_resp').value, reason: $('ad_reason').value });
        if (j.status !== 200) { $('ad_err').textContent = j.message; $('ad_err').hidden = false; return; }
        hide('m_add'); toast('success', j.message); await loadAccounts(); $('p_staff').value = j.id; tab('perm'); loadStaff(j.id);
    });
    $('b_new').addEventListener('click', () => { $('a_state').value = 'eligible'; renderAccounts(); tab('accounts'); Swal.fire({ icon: 'info', title: 'New staff assignment', text: 'No account is created here. Choose an existing enabled account below and bring it onto the panel; it starts with everything Blocked. Sign-in names and passwords are a separate decision (decision 4).' }); });

    // ---------------------------------------------------------------- permission panel
    async function loadMenus() { const j = await api('/menus'); MENUS = j.menus || []; renderTemplates(); }
    async function loadStaff(id) {
        if (!id) { STAFF = null; PERMS = {}; $('p_head').hidden = true; $('p_body').innerHTML = '<tr><td colspan="4" class="muted" style="padding:16px">Choose a staff member above.</td></tr>'; $('p_count').textContent = '–'; return; }
        const j = await api('/one?id=' + id); if (j.status !== 200) { toast('error', j.message); return; }
        STAFF = j.staff; PERMS = j.permissions || {}; renderStaffHead(); renderPerm();
    }
    function renderStaffHead() {
        const s = STAFF, st = s.status;
        $('p_sub').textContent = (s.emp_name || s.username) + ' · ' + s.username + ' · group today: ' + (s.group_name || s.group_id) + ' (not applied while on the panel)';
        $('p_head').hidden = false;
        $('p_head').innerHTML = '<div class="gh"><div>Status: ' + (st === 'active' ? '<span class="pill p-green">Active</span>' : st === 'suspended' ? '<span class="pill p-amber">Suspended</span>' : '<span class="pill p-red">Deactivated</span>') + ' · Department label: <b>' + esc(s.department || '—') + '</b>' + (s.responsibilities ? ' · ' + esc(s.responsibilities) : '') + ' · Payment roles held: <b>' + ((s.roles || []).map(r => r + ' ' + ROLE_NAMES[r]).join(', ') || 'none') + '</b></div>' +
            '<div>' + (st !== 'active' ? '<button class="btn3 g" data-status="active">Reactivate</button> ' : '') + (st !== 'suspended' ? '<button class="btn3" data-status="suspended">Suspend</button> ' : '') + (st !== 'deactivated' ? '<button class="btn3 r" data-status="deactivated">Deactivate</button> ' : '') + '<button class="btn3" data-label="1">Label</button> <button class="btn3" data-remove="1">Take off the panel</button></div></div>';
    }
    $('p_head').addEventListener('click', e => {
        const b = e.target.closest('button'); if (!b || !STAFF) return;
        if (b.dataset.status) ask('Change status', '<div class="field"><span>Staff member</span><span><b>' + esc(STAFF.emp_name || STAFF.username) + '</b></span></div><div class="field"><span>Current</span><span>' + esc(STAFF.status) + '</span></div><div class="field"><span>Proposed</span><span><b>' + esc(b.dataset.status) + '</b></span></div>' + (b.dataset.status !== 'active' ? '<div class="warnbox">Every open session of this account is ended at once and its next request is refused.</div>' : ''), async reason => post('/status', { staff_id: STAFF.id, status: b.dataset.status, reason }));
        if (b.dataset.remove) ask('Take off the panel', '<div class="warnbox">The account goes back to its group list (<b>' + esc(STAFF.group_name) + '</b>). Its Open / Blocked rows are kept but no longer applied.</div>', async reason => post('/remove', { staff_id: STAFF.id, reason }));
        if (b.dataset.label) {
            const depts = <?= json_encode($departments ?? []) ?>;
            ask('Department label and responsibilities', '<div class="field"><span>Department label</span><select class="inp" id="l_dept"><option value="">— none —</option>' + depts.map(d => '<option' + (d === STAFF.department ? ' selected' : '') + '>' + esc(d) + '</option>').join('') + '</select></div><div class="field"><span>Responsibilities</span><input class="inp" id="l_resp" maxlength="500" value="' + esc(STAFF.responsibilities || '') + '"></div><div class="small muted">Labels grant nothing.</div>', async reason => post('/label', { staff_id: STAFF.id, department: $('l_dept').value, responsibilities: $('l_resp').value, reason }));
        }
    });
    function stateOf(m) { return (PERMS[m.id] && PERMS[m.id].state) === 'open' ? 'open' : 'blocked'; }
    function renderPerm() {
        if (!STAFF) return;
        const q = $('p_q').value.trim().toLowerCase(), f = $('p_filter').value;
        let open = 0, total = 0, html = '', cat = null, seenUrl = new Set();
        MENUS.forEach(m => {
            const st = stateOf(m); if (!m.protected) { total++; if (st === 'open') open++; }
            if (q && !(m.name + ' ' + m.url + ' ' + m.category).toLowerCase().includes(q)) return;
            if (f === 'open' && st !== 'open') return; if (f === 'blocked' && (st !== 'blocked' || m.protected)) return; if (f === 'rights' && !m.right) return;
            if (m.category !== cat) { cat = m.category; const ids = MENUS.filter(x => x.category === cat && !x.protected && !x.role).map(x => x.id); html += '<tr class="grp"><td colspan="4"><div class="gh"><span>' + esc(cat) + '</span>' + (ids.length ? '<span><button class="btn3" data-bulk="open" data-cat="' + esc(cat) + '">Open all in this group</button> <button class="btn3" data-bulk="blocked" data-cat="' + esc(cat) + '">Block all</button></span>' : '') + '</div></td></tr>'; }
            const p = PERMS[m.id];
            const tags = (m.role ? ' <span class="pill p-orchid">role ' + m.role + ' · ' + esc(ROLE_NAMES[m.role]) + '</span>' : '') + (m.right ? ' <span class="pill p-grey">action right</span>' : '') + (m.same_url.length ? ' <span class="pill p-grey" title="same URL as menu id ' + m.same_url.join(', ') + '">shares URL</span>' : '');
            const ctl = m.protected ? '<span class="pill p-red">Super Admin only</span>' : '<span class="sw ' + st + '" role="switch" tabindex="0" aria-checked="' + (st === 'open') + '" data-id="' + m.id + '"><span class="o">Open</span><span class="b">Blocked</span></span>';
            html += '<tr><td>' + esc(m.name) + tags + '</td><td class="small"><code>' + esc(m.url) + '</code></td><td>' + ctl + '</td><td class="small muted">' + (p ? dt(p.set_at) + ' · ' + esc(p.set_by) + ' · "' + esc(p.reason) + '"' : (m.protected ? 'no switch; the controller refuses every other account' : '—')) + '</td></tr>';
        });
        $('p_body').innerHTML = html || '<tr><td colspan="4" class="muted" style="padding:14px">No row matches.</td></tr>';
        $('p_count').textContent = open + ' open · ' + (total - open) + ' blocked · ' + total + ' switchable rows';
    }
    ['p_q', 'p_filter'].forEach(id => $(id).addEventListener('input', renderPerm));
    $('p_staff').addEventListener('change', e => loadStaff(+e.target.value));
    $('pageSearch').addEventListener('input', e => { $('p_q').value = e.target.value; if (STAFF) { tab('perm'); renderPerm(); } });
    $('p_body').addEventListener('click', e => {
        const sw = e.target.closest('.sw'), bk = e.target.closest('[data-bulk]');
        if (sw) switchRow(+sw.dataset.id);
        if (bk) bulkCat(bk.dataset.cat, bk.dataset.bulk);
    });
    $('p_body').addEventListener('keydown', e => { const sw = e.target.closest('.sw'); if (sw && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); switchRow(+sw.dataset.id); } });
    function switchRow(id) {
        const m = MENUS.find(x => x.id === id); if (!m || !STAFF) return;
        const cur = stateOf(m), to = cur === 'open' ? 'blocked' : 'open';
        ask((to === 'open' ? 'Open' : 'Block') + ' this permission?', '<div class="field"><span>Staff member</span><span><b>' + esc(STAFF.emp_name || STAFF.username) + '</b> (' + esc(STAFF.username) + ')</span></div><div class="field"><span>Function</span><span><b>' + esc(m.name) + '</b> <code>' + esc(m.url) + '</code></span></div><div class="field"><span>Current setting</span><span>' + (cur === 'open' ? '<span class="pill p-green">Open</span>' : '<span class="pill p-red">Blocked</span>') + '</span></div><div class="field"><span>Proposed setting</span><span>' + (to === 'open' ? '<span class="pill p-green">Open</span>' : '<span class="pill p-red">Blocked</span>') + '</span></div>' + (m.role && to === 'open' ? '<div class="warnbox">Payment role ' + m.role + ' (' + esc(ROLE_NAMES[m.role]) + '). After this the other two payment roles are refused for this person.</div>' : ''),
            async reason => post('/set', { staff_id: STAFF.id, menu_id: id, state: to, reason }));
    }
    function bulkCat(cat, state) {
        if (!STAFF) return;
        const rows = MENUS.filter(m => m.category === cat && !m.protected && !m.role && stateOf(m) !== state);
        const skipped = MENUS.filter(m => m.category === cat && (m.role || m.protected));
        if (!rows.length) { toast('info', 'Every switchable row in "' + cat + '" is already ' + state + '.'); return; }
        ask((state === 'open' ? 'Open' : 'Block') + ' all in ' + cat, '<div class="warnbox">This will switch <b>' + rows.length + ' rows</b> to <b>' + state + '</b>:<br>' + rows.map(m => esc(m.name)).join(' · ') + '</div>' + (skipped.length ? '<div class="small muted" style="margin-top:6px">Not included (never in bulk): ' + skipped.map(m => esc(m.name)).join(' · ') + '</div>' : '') + '<div class="small muted" style="margin-top:6px">Each row is logged as its own line with this reason.</div>',
            async reason => post('/bulk', { staff_id: STAFF.id, menu_ids: rows.map(m => m.id), state, reason }));
    }

    // ---------------------------------------------------------------- templates (suggestion lists by URL; payment roles never included)
    const TEMPLATES = {
        'Tour Reservations': ['tour/intake', 'tour/intake/recalculate', 'tour/board', 'report/confirmed_tours', 'tour/booking_confirm', 'master_files/customer', 'tour/ticket_stock'],
        'Hotel Reservations': ['tour/hotel_bookings', 'dashboard2/hotel_management/hotel_reservation', 'dashboard2/hotel_management/hotel_booking', 'dashboard2/hotel_management/hotel_payment', 'master_files/hotel_master', 'master_files/hotel_rate_v2'],
        'Transport & Guide Handling': ['tour/transport_ops', 'tour/transport_ops/crew_change', 'tour/transport_ops/driver_advance', 'dashboard2/transport/driver_allow/tour_transport', 'dashboard2/transport/driver_logsheet', 'dashboard2/transport/driver_settlement', 'dashboard2/transport/driver_expense', 'master_files/crew_people', 'master_files/vehicle', 'tour/tour_payment_dashboard/request'],
        'Accounts': ['tour/tour_payment_dashboard', 'tour/tour_payment_dashboard/request', 'report/bank_payment_check', 'report/bank_payment_check/finalized_records', 'accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'master_files/supplier_bank', 'master_files/supplier_accounts', 'accounts/journal_entry', 'accounts/creditor_payment', 'accounts/voucher_payments/0'],
        'Management Approver': ['tour/tour_payment_dashboard', 'tour/tour_payment_dashboard/payables', 'master_files/supplier_bank/verify', 'accounts/daily_bank_rec/review'],
        'Bank Authoriser': ['tour/tour_payment_dashboard', 'report/bank_payment_check', 'report/bank_payment_check/finalized_records'],
        'Pilot / Operations Allocation': ['tour/board', 'tour/transport_ops', 'dashboard2/transport/driver_allow/pending_tours', 'dashboard2/transport/driver_allow/allocated_tours']
    };
    const ROLE_URLS_NOTE = { 'Accounts': 'B', 'Management Approver': 'A', 'Bank Authoriser': 'C' };
    function renderTemplates() {
        const g = $('tpl_grid'); let n = 0;
        g.innerHTML = Object.keys(TEMPLATES).map(name => {
            const rows = TEMPLATES[name].map(u => MENUS.find(m => m.url === u)).filter(Boolean).filter(m => !m.role && !m.protected); n++;
            return '<div class="tpl"><h6>' + esc(name) + '</h6><ul>' + rows.map(m => '<li>' + esc(m.name) + ' <code>' + esc(m.url) + '</code></li>').join('') + '</ul><div class="small muted">Would open ' + rows.length + ' rows · payment role: ' + (ROLE_URLS_NOTE[name] ? ROLE_URLS_NOTE[name] + ' (' + esc(ROLE_NAMES[ROLE_URLS_NOTE[name]]) + ') is opened separately, never by the template' : 'none') + '</div><button class="btn3" data-tpl="' + esc(name) + '" style="margin-top:8px">Preview for the chosen staff member</button></div>';
        }).join('');
        $('tpl_pill').textContent = n + ' templates';
    }
    $('tpl_grid').addEventListener('click', e => {
        const b = e.target.closest('[data-tpl]'); if (!b) return;
        if (!STAFF) { toast('info', 'Choose a staff member on the Permission panel first.'); tab('perm'); return; }
        const rows = TEMPLATES[b.dataset.tpl].map(u => MENUS.find(m => m.url === u)).filter(Boolean).filter(m => !m.role && !m.protected && stateOf(m) !== 'open');
        if (!rows.length) { toast('info', 'Every row of this template is already Open for ' + (STAFF.emp_name || STAFF.username) + '.'); return; }
        ask('Template — ' + b.dataset.tpl, '<div class="warnbox">For <b>' + esc(STAFF.emp_name || STAFF.username) + '</b> this would switch <b>' + rows.length + ' rows</b> from Blocked to Open:<br>' + rows.map(m => esc(m.name)).join(' · ') + '</div><div class="small muted" style="margin-top:6px">Nothing else changes; each row is logged on its own.</div>', async reason => post('/bulk', { staff_id: STAFF.id, menu_ids: rows.map(m => m.id), state: 'open', reason }));
    });

    // ---------------------------------------------------------------- history
    async function loadHistory() { const j = await api('/history'); HIST = j.history || []; renderHistory(); }
    function renderHistory() {
        const q = $('h_q').value.trim().toLowerCase(), k = $('h_kind').value;
        const rows = HIST.filter(h => (!k || h.action === k) && (!q || JSON.stringify(h).toLowerCase().includes(q)));
        const who = {}; ACCOUNTS.forEach(a => { if (a.staff_id) who[a.staff_id] = a.name || a.username; });
        $('h_body').innerHTML = rows.map(h => '<tr><td class="small">' + dt(h.at) + '</td><td>' + esc(h.by_name || h.by_user) + '</td><td>' + esc(who[h.staff_id] || ('#' + h.staff_id)) + '</td><td>' + (h.action === 'refused' ? '<span class="pill p-amber">refused</span>' : h.action === 'opened' ? '<span class="pill p-green">opened</span>' : h.action === 'blocked' ? '<span class="pill p-red">blocked</span>' : '<span class="pill p-grey">' + esc(h.action) + '</span>') + '<div class="small muted">' + esc(h.detail) + '</div></td><td class="small">' + (h.menu_name ? esc(h.menu_name) + '<br><code>' + esc(h.menu_url) + '</code>' : '—') + '</td><td class="small">' + esc(h.from_state || '—') + ' → ' + esc(h.to_state || '—') + '</td><td class="small">"' + esc(h.reason) + '"</td></tr>').join('') || '<tr><td colspan="7" class="muted" style="padding:14px">No entries yet.</td></tr>';
        $('h_pill').textContent = HIST.length + ' entries';
    }
    ['h_q', 'h_kind'].forEach(id => $(id).addEventListener('input', renderHistory));

    // ---------------------------------------------------------------- the reason dialog (one for every change)
    let pendingFn = null;
    function ask(title, bodyHtml, fn) { $('r_title').textContent = title; $('r_body').innerHTML = bodyHtml; $('r_reason').value = ''; $('r_err').hidden = true; pendingFn = fn; show('m_reason'); setTimeout(() => $('r_reason').focus(), 300); }
    $('r_ok').addEventListener('click', async () => {
        const reason = $('r_reason').value.trim();
        if (reason.length < REASON_MIN) { $('r_err').textContent = 'A reason of at least ' + REASON_MIN + ' characters is required. Nothing has been changed.'; $('r_err').hidden = false; $('r_reason').focus(); return; }
        if (!pendingFn) return;
        const j = await pendingFn(reason);
        if (j.status !== 200) { $('r_err').innerHTML = esc(j.message) + (j.refused ? '<div class="small" style="margin-top:4px">The refusal was logged.</div>' : ''); $('r_err').hidden = false; return; }
        hide('m_reason');
        if (j.refused && j.refused.length) Swal.fire({ icon: 'warning', title: j.message, html: j.refused.map(r => esc(r.message)).join('<br>') }); else toast('success', j.message);
        await loadAccounts(); if (STAFF) await loadStaff(STAFF.id); if (!$('v_hist').hidden) loadHistory();
    });

    // ---------------------------------------------------------------- positions (tf-positions-r1)
    const DEPTS_P = <?= json_encode(array_values($departments ?? [])) ?>;
    let POSITIONS = [];
    async function loadPositions() {
        const j = await api('/positions'); POSITIONS = j.positions || [];
        const body = $('pos_body'); if (!body) return;
        if (!POSITIONS.length) { body.innerHTML = '<tr><td colspan="6" class="muted">No positions yet. Create the position first, then assign the person who holds it.</td></tr>'; return; }
        body.innerHTML = POSITIONS.map(p => {
            const h = p.holder;
            const who = h ? esc(h.username) : '<span class="muted">vacant</span>';   // the position reads by the holder's username (Shantha, 7 Nov 2026)
            const acc = h ? ((h.enabled ? '<span class="pill p-green">Sign-in enabled</span>' : '<span class="pill p-grey">Sign-in disabled</span>') + ' ' + (h.status === 'active' ? '<span class="pill p-green">Panel active</span>' : '<span class="pill p-amber">Panel ' + esc(h.status || '—') + '</span>') + ' <span class="pill">' + h.open_count + ' permission' + (h.open_count === 1 ? '' : 's') + '</span>') : '—';
            const act = p.status !== 'open' ? '<span class="pill p-grey">Closed</span>'
                : h ? '<button class="btn2" type="button" data-pperm="' + h.staff_id + '">Permissions</button> <button class="btn2" type="button" data-pend="' + p.id + '">End holding</button>'
                    : '<button class="btn2 g" type="button" data-passign="' + p.id + '">Assign person</button>';
            return '<tr><td><b>' + esc(p.title) + '</b> &rarr; ' + who + '</td><td>' + esc(p.department || '—') + '</td><td>' + (h ? dt(h.since) : '—') + '</td><td>' + (h ? esc(h.name) : '—') + '</td><td>' + acc + '</td><td>' + act + '</td></tr>';
        }).join('');
    }
    $('b_newpos') && $('b_newpos').addEventListener('click', () => {
        ask('New position', '<div class="field"><span>Position title</span><input class="inp" id="np_title" maxlength="120" placeholder="e.g. Director of Operations"></div>'
            + '<div class="field"><span>Department label</span><select class="inp" id="np_dept"><option value="">— none —</option>' + DEPTS_P.map(d => '<option>' + esc(d) + '</option>').join('') + '</select></div>'
            + '<div class="field"><span>Responsibilities</span><input class="inp" id="np_resp" maxlength="500" placeholder="what the holder of this position is expected to do"></div>'
            + '<div class="small muted">A position grants nothing. Permissions are opened on the person who holds it, one row at a time.</div>',
            async reason => { const j = await post('/position', { title: $('np_title').value, department: $('np_dept').value, responsibilities: $('np_resp').value, reason }); if (j.status === 200) loadPositions(); return j; });
    });
    document.addEventListener('click', e => {
        const a = e.target.closest('[data-passign]'), en = e.target.closest('[data-pend]'), pp = e.target.closest('[data-pperm]');
        if (a) {
            const p = POSITIONS.find(x => +x.id === +a.dataset.passign); if (!p) return;
            const held = new Set(POSITIONS.filter(x => x.holder).map(x => x.holder.staff_id));
            const opts = ACCOUNTS.filter(x => x.on_panel && x.staff_id && !held.has(x.staff_id));
            if (!opts.length) { toast('info', 'Nobody on the panel is free to assign. The person needs their own sign-in first; creating it puts them on the panel with nothing open.'); return; }
            ask('Assign the person who holds "' + p.title + '"', '<div class="field"><span>Person</span><select class="inp" id="pa_staff">' + opts.map(x => '<option value="' + x.staff_id + '">' + esc((x.name || x.username) + ' (' + x.username + ')') + '</option>').join('') + '</select></div><div class="small muted">This records who holds the position. It opens no permission and changes no status.</div>',
                async reason => { const j = await post('/position/assign', { position_id: p.id, staff_id: +$('pa_staff').value, reason }); if (j.status === 200) loadPositions(); return j; });
        } else if (en) {
            const p = POSITIONS.find(x => +x.id === +en.dataset.pend); if (!p || !p.holder) return;
            ask('End ' + p.holder.name + '\'s holding of "' + p.title + '"', '<div class="small muted">The record of who held it, and when, is kept. Their permissions and status are not changed by this &mdash; suspend them on the panel if their access should end.</div>',
                async reason => { const j = await post('/position/end', { position_id: p.id, reason }); if (j.status === 200) loadPositions(); return j; });
        } else if (pp) {
            $('p_staff').value = pp.dataset.pperm; tab('perm'); loadStaff(+pp.dataset.pperm);
        }
    });

    Promise.all([loadMenus(), loadAccounts(), loadPositions()]).then(() => { if (OPEN_STAFF) { $('p_staff').value = OPEN_STAFF; tab('perm'); loadStaff(OPEN_STAFF); } });
})();
</script>
</body>
</html>
