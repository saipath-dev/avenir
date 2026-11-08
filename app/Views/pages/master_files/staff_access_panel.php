<?php
/**
 * tf-panel2-r1 (8 Nov 2026, Shantha) — the redesigned Staff Access Control Panel (the "Permission panel" tab of Staff Assignment).
 * By person: one person's every page and action, in Travel Sales / Ground Handling / Accounts / Shared & Administration, each row
 *   Open · Blocked · Not set; changes are staged, reviewed (additions and removals listed) and saved with one reason.
 * By category: who holds access in a work category — enabled people counted once, inactive accounts and the Super Admin apart.
 * The screen only stages and shows; the server decides (StaffAssignment::set / unset, the three payment roles, protected rows).
 */
$sapBase = base_url('master_files/staff');
?>
<style>
.sap{--p:#842484;--p2:#a64aa6;--pl:#f6eef6;--pll:#fbf7fb;--g:#78b848;--gd:#5a9a2e;--gl:#eef7e6;--r:#c0392b;--rl:#fbeceb;--a:#b7791f;--al:#fdf4e3;--ink:#1f1a24;--mut:#6b6272;--ln:#e8e1ea;--bg:#ffffff;font-size:14px;color:var(--ink)}
.sap *{box-sizing:border-box}
.sap .sap-top{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin:4px 0 14px}
.sap .sap-mode{display:inline-flex;background:var(--pl);border-radius:999px;padding:4px;gap:4px}
.sap .sap-mode button{border:0;background:transparent;padding:8px 18px;border-radius:999px;font-weight:600;color:var(--p);cursor:pointer}
.sap .sap-mode button[aria-pressed=true]{background:var(--p);color:#fff;box-shadow:0 2px 6px rgba(132,36,132,.25)}
.sap .sap-note{color:var(--mut);font-size:12.5px}
.sap .sap-card{background:var(--bg);border:1px solid var(--ln);border-radius:14px;box-shadow:0 1px 2px rgba(31,26,36,.04)}
.sap .sap-find{padding:16px;margin-bottom:14px;position:relative}
.sap .sap-find label{font-weight:600;display:block;margin-bottom:8px}
.sap .sap-inp{width:100%;border:1px solid #d9cfdc;border-radius:10px;padding:11px 14px;font-size:15px;outline:none;background:#fff}
.sap .sap-inp:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(132,36,132,.12)}
.sap .sap-res{position:absolute;left:16px;right:16px;top:calc(100% - 8px);z-index:30;background:#fff;border:1px solid var(--ln);border-radius:12px;box-shadow:0 12px 28px rgba(31,26,36,.14);max-height:320px;overflow:auto}
.sap .sap-res button{display:flex;width:100%;text-align:left;border:0;background:#fff;padding:10px 14px;gap:10px;align-items:center;cursor:pointer;border-bottom:1px solid #f3eef4}
.sap .sap-res button:hover,.sap .sap-res button:focus{background:var(--pll)}
.sap .sap-av{width:38px;height:38px;border-radius:50%;background:var(--p);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex:none;letter-spacing:.5px}
.sap .sap-who{position:sticky;top:64px;z-index:20;display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding:14px 16px;margin-bottom:14px;border-left:5px solid var(--p);background:linear-gradient(90deg,var(--pll),#fff)}
.sap .sap-who .nm{font-size:17px;font-weight:700}
.sap .sap-who .sub{color:var(--mut);font-size:12.5px}
.sap .sap-who .sp{flex:1}
.sap-mini{position:fixed;left:50%;transform:translateX(-50%);top:72px;z-index:1040;display:flex;align-items:center;gap:10px;background:#842484;color:#fff;border-radius:999px;padding:6px 16px 6px 6px;box-shadow:0 8px 20px rgba(132,36,132,.35);font-size:13px;max-width:calc(100vw - 24px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sap-mini .sap-av{width:28px;height:28px;font-size:11px;background:#fff;color:#842484;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700}
.sap-mini code{color:#fff;background:rgba(255,255,255,.18);padding:1px 6px;border-radius:6px}
.sap .sap-kpi{display:flex;gap:8px;flex-wrap:wrap}
.sap .chip{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:3px 10px;font-size:12px;font-weight:600;white-space:nowrap;border:1px solid transparent}
.sap .c-open{background:var(--gl);color:var(--gd);border-color:#d3e9c2}
.sap .c-part{background:var(--al);color:var(--a);border-color:#f2dfb5}
.sap .c-block{background:var(--rl);color:var(--r);border-color:#f2cfcb}
.sap .c-none{background:#f2f0f3;color:var(--mut);border-color:#e6e2e8}
.sap .c-p{background:var(--pl);color:var(--p);border-color:#ead6ea}
.sap .c-lock{background:#2b2230;color:#fff}
.sap .sap-tools{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px}
.sap .sap-tools .sap-inp{flex:1;min-width:200px;padding:9px 12px;font-size:14px}
.sap .sap-filter{display:inline-flex;gap:6px;flex-wrap:wrap}
.sap .sap-filter button{border:1px solid var(--ln);background:#fff;border-radius:999px;padding:6px 12px;font-size:12.5px;font-weight:600;color:var(--mut);cursor:pointer}
.sap .sap-filter button[aria-pressed=true]{border-color:var(--p);color:var(--p);background:var(--pll)}
.sap .sap-cat{margin-bottom:14px;overflow:hidden}
.sap .sap-cat>summary,.sap .sap-task>summary{list-style:none;cursor:pointer}
.sap .sap-cat>summary::-webkit-details-marker,.sap .sap-task>summary::-webkit-details-marker{display:none}
.sap .sap-cat>summary{display:flex;align-items:center;gap:12px;padding:14px 16px;background:linear-gradient(90deg,var(--p),var(--p2));color:#fff;flex-wrap:wrap}
.sap .sap-cat>summary .t{font-size:16px;font-weight:700}
.sap .sap-cat>summary .d{font-size:12.5px;opacity:.9}
.sap .sap-cat>summary .sp{flex:1}
.sap .sap-cat.shared>summary{background:linear-gradient(90deg,#4b3f52,#6a5d72)}
.sap .tw-ar{display:inline-block;transition:transform .15s;font-size:12px}
.sap details[open]>summary .tw-ar{transform:rotate(90deg)}
.sap .sap-task{border-top:1px solid var(--ln)}
.sap .sap-task>summary{display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--pll);flex-wrap:wrap}
.sap .sap-task>summary .t{font-weight:700;color:var(--p)}
.sap .sap-task>summary .d{color:var(--mut);font-size:12.5px}
.sap .sap-task>summary .sp{flex:1}
.sap .gbtn{border:1px solid var(--ln);background:#fff;border-radius:8px;padding:5px 10px;font-size:12px;font-weight:600;cursor:pointer;color:var(--ink)}
.sap .gbtn.o{border-color:#cfe5bd;color:var(--gd)}.sap .gbtn.b{border-color:#f0cdc9;color:var(--r)}.sap .gbtn.n{color:var(--mut)}
.sap .gbtn:hover{filter:brightness(.97)}
.sap .gbtn.wh{background:rgba(255,255,255,.14);border-color:rgba(255,255,255,.45);color:#fff}
.sap table.sap-t{width:100%;border-collapse:collapse}
.sap .sap-t th{font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--mut);text-align:left;padding:8px 16px;border-bottom:1px solid var(--ln);background:#fff}
.sap .sap-t td{padding:10px 16px;border-bottom:1px solid #f3eef4;vertical-align:top}
.sap .sap-t tr.chg td{background:#fffbe9}
.sap .sap-t .wd b{display:block;font-weight:600}
.sap .sap-t .wd span{color:var(--mut);font-size:12.5px}
.sap .url{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;color:var(--p);background:var(--pll);padding:2px 6px;border-radius:6px;word-break:break-all;display:inline-block}
.sap .seg{display:inline-flex;border:1px solid var(--ln);border-radius:10px;overflow:hidden;background:#fff}
.sap .seg button{border:0;background:#fff;padding:7px 10px;font-size:12.5px;font-weight:600;color:var(--mut);cursor:pointer;min-width:58px;white-space:nowrap}
.sap .seg button+button{border-left:1px solid var(--ln)}
.sap .seg button[aria-pressed=true].o{background:var(--g);color:#fff}
.sap .seg button[aria-pressed=true].b{background:var(--r);color:#fff}
.sap .seg button[aria-pressed=true].n{background:#7d7385;color:#fff}
.sap .seg button:focus-visible{outline:2px solid var(--p);outline-offset:-2px}
.sap .was{font-size:11.5px;color:var(--a);margin-top:4px;font-weight:600}
.sap .lc{font-size:12px;color:var(--mut);line-height:1.45}
.sap .lc q{color:var(--ink)}
.sap .sap-empty{padding:28px;text-align:center;color:var(--mut)}
.sap .sap-bar{position:sticky;bottom:12px;z-index:25;display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 16px;margin-top:10px;background:#2b2230;color:#fff;border-radius:14px;box-shadow:0 10px 24px rgba(31,26,36,.25)}
.sap .sap-bar .sp{flex:1}
.sap .btnp{border:0;border-radius:10px;padding:10px 18px;font-weight:700;cursor:pointer;background:var(--g);color:#fff}
.sap .btnp:disabled{opacity:.5;cursor:not-allowed}
.sap .btnq{border:1px solid rgba(255,255,255,.4);border-radius:10px;padding:9px 14px;font-weight:600;cursor:pointer;background:transparent;color:#fff}
.sap .sap-ppl{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;padding:14px}
.sap .sap-pc{border:1px solid var(--ln);border-radius:12px;padding:12px 14px;background:#fff;cursor:pointer;text-align:left}
.sap .sap-pc:hover{border-color:var(--p);box-shadow:0 4px 12px rgba(132,36,132,.1)}
.sap .sap-pc .h{display:flex;gap:10px;align-items:center;margin-bottom:8px}
.sap .sap-pc .tl{display:flex;flex-wrap:wrap;gap:6px}
.sap .sap-big{font-size:34px;font-weight:800;color:var(--p);line-height:1}
.sap .sap-sec{padding:12px 16px;border-top:1px solid var(--ln);font-weight:700;color:var(--mut);font-size:12px;text-transform:uppercase;letter-spacing:.05em;background:#fcfafc}
.sap-modal{position:fixed;inset:0;background:rgba(31,26,36,.55);z-index:2000;display:flex;align-items:flex-start;justify-content:center;padding:4vh 12px;overflow:auto}
.sap-modal .box{background:#fff;border-radius:16px;max-width:760px;width:100%;box-shadow:0 20px 50px rgba(0,0,0,.3);overflow:hidden;font-size:14px;color:#1f1a24}
.sap-modal .hd{background:linear-gradient(90deg,#842484,#a64aa6);color:#fff;padding:16px 20px}
.sap-modal .hd h5{margin:0;font-size:17px;font-weight:700;color:#fff}
.sap-modal .bd{padding:16px 20px;max-height:62vh;overflow:auto}
.sap-modal .ft{padding:14px 20px;border-top:1px solid #e8e1ea;display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap}
.sap-modal h6{margin:14px 0 6px;font-weight:700;font-size:13px}
.sap-modal ul{margin:0;padding-left:18px}
.sap-modal li{margin:3px 0}
.sap-modal textarea{width:100%;min-height:76px;border:1px solid #d9cfdc;border-radius:10px;padding:10px;font-size:14px}
.sap-modal .warn{background:#fdf4e3;border:1px solid #f2dfb5;color:#7a5512;border-radius:10px;padding:10px 12px;margin:10px 0;font-size:13px}
.sap-modal .err{background:#fbeceb;border:1px solid #f2cfcb;color:#9b2c21;border-radius:10px;padding:10px 12px;margin-top:10px}
.sap-modal .bq{border:1px solid #d9cfdc;background:#fff;border-radius:10px;padding:9px 16px;font-weight:600;cursor:pointer}
.sap-modal .bp{border:0;background:#78b848;color:#fff;border-radius:10px;padding:10px 18px;font-weight:700;cursor:pointer}
.sap-modal .url{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12px;color:#842484}
@media (max-width:760px){
  .sap .sap-who{top:56px}
  .sap-mini{top:78px}
  .sap .sap-t thead{display:none}
  .sap .sap-t tr{display:block;border-bottom:1px solid var(--ln);padding:8px 0}
  .sap .sap-t td{display:block;border:0;padding:4px 14px}
  .sap .sap-t td[data-l]::before{content:attr(data-l);display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--mut);margin-bottom:2px}
  .sap .seg button{min-width:0;padding:9px 12px}
  .sap .sap-cat>summary .gbtn,.sap .sap-task>summary .gbtn{padding:6px 9px}
  .sap .sap-mode button{padding:8px 13px}
}
</style>

<section id="v_perm">
<div class="sap" id="sap">
  <div class="sap-top">
    <div class="sap-mode" role="group" aria-label="View permissions">
      <button type="button" data-mode="person" aria-pressed="true">By person</button>
      <button type="button" data-mode="category" aria-pressed="false">By category</button>
    </div>
    <div class="sap-note">Live from the system · every change is reviewed, needs a reason and is kept in the history</div>
  </div>

  <!-- ===================== by person ===================== -->
  <div id="sap_person">
    <div class="sap-card sap-find">
      <label for="sap_q">Find staff by name or login</label>
      <input class="sap-inp" id="sap_q" type="search" autocomplete="off" placeholder="e.g. Iqbal, IqbaalJ, Malindu…" aria-controls="sap_res">
      <div class="sap-res" id="sap_res" hidden role="listbox"></div>
    </div>
    <div id="sap_who_wrap"></div>
    <div class="sap-mini" id="sap_mini" hidden aria-hidden="true"></div>
    <div id="sap_body"><div class="sap-card sap-empty">Search for a person above to see and change their access.</div></div>
    <div class="sap-bar" id="sap_bar" hidden>
      <span id="sap_bar_t">0 changes</span><span class="sp"></span>
      <button class="btnq" type="button" id="sap_discard">Discard</button>
      <button class="btnp" type="button" id="sap_review">Review changes</button>
    </div>
  </div>

  <!-- ===================== by category ===================== -->
  <div id="sap_category" hidden>
    <div class="sap-card sap-find">
      <label>Work category</label>
      <div class="sap-filter" id="sap_cats" role="group" aria-label="Work category"></div>
    </div>
    <div id="sap_cat_body"><div class="sap-card sap-empty">Choose a work category.</div></div>
  </div>
</div>
</section>

<script>
(function () {
    'use strict';
    const BASE = <?= json_encode($sapBase) ?>;
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const api = async p => { const r = await fetch(BASE + p, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }); return r.json(); };
    const post = async (p, b) => { const r = await fetch(BASE + p, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(b) }); return r.json(); };
    const initials = s => String(s || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
    const dt = s => esc(String(s || '').slice(0, 16));
    const LBL = { open: 'Open', blocked: 'Blocked', unset: 'Not set' };

    let MAP = null, ROLE_NAMES = {}, ACCOUNTS = [], STAFF = null, PERMS = {}, LAST = {}, PENDING = {}, FILTER = 'all', Q = '', CAT = 'sales';
    const OPEN_STATE = { cats: {}, tasks: {} };

    // ---------------------------------------------------------------- data
    async function ensureMap() { if (MAP) return; const j = await api('/map'); MAP = j.categories || []; ROLE_NAMES = j.role_names || {}; }
    async function loadAccounts() { const j = await api('/accounts'); ACCOUNTS = j.accounts || []; }
    const screens = () => MAP.flatMap(c => c.tasks.flatMap(t => t.screens.map(s => Object.assign({ cat: c.key, task: t.key }, s))));
    const saved = s => (PERMS[s.id] && PERMS[s.id].state) || 'unset';
    const now = s => PENDING[s.id] ? PENDING[s.id].to : saved(s);

    // ---------------------------------------------------------------- mode
    document.querySelectorAll('#sap [data-mode]').forEach(b => b.addEventListener('click', () => mode(b.dataset.mode)));
    function mode(m) {
        document.querySelectorAll('#sap [data-mode]').forEach(b => b.setAttribute('aria-pressed', b.dataset.mode === m ? 'true' : 'false'));
        $('sap_person').hidden = m !== 'person'; $('sap_category').hidden = m !== 'category';
        if (m === 'category') renderCats();
    }

    // ---------------------------------------------------------------- search a person
    $('sap_q').addEventListener('input', renderResults);
    $('sap_q').addEventListener('focus', renderResults);
    document.addEventListener('click', e => { if (!e.target.closest('.sap-find')) $('sap_res').hidden = true; });
    function renderResults() {
        const q = $('sap_q').value.trim().toLowerCase();
        const list = ACCOUNTS.filter(a => !a.is_driver && (!q || ((a.name || '') + ' ' + a.username + ' ' + a.group).toLowerCase().includes(q)));
        $('sap_res').innerHTML = list.slice(0, 30).map(a => '<button type="button" role="option" data-pick="' + a.user_id + '"><span class="sap-av">' + esc(initials(a.name || a.username)) + '</span><span><b>' + esc(a.name || a.username) + '</b> · <span class="url">' + esc(a.username) + '</span><br><span class="sap-note">' + esc(a.group) + ' · ' + (a.is_admin ? 'Super Admin — universal access' : a.on_panel ? (a.open_count + ' open · ' + (a.enabled ? (a.status === 'active' ? 'enabled' : a.status) : 'sign-in disabled')) : 'not on the panel') + '</span></span></button>').join('') || '<div class="sap-empty">No one matches.</div>';
        $('sap_res').hidden = false;
    }
    $('sap_res').addEventListener('click', e => { const b = e.target.closest('[data-pick]'); if (b) pick(+b.dataset.pick); });
    async function pick(userId) {
        const a = ACCOUNTS.find(x => x.user_id === userId); $('sap_res').hidden = true; if (!a) return;
        $('sap_q').value = a.name ? a.name + ' (' + a.username + ')' : a.username;
        if (a.is_admin) { STAFF = null; PENDING = {}; $('sap_who_wrap').innerHTML = whoCard({ emp_name: a.name, username: a.username, group_name: a.group, admin: true }); $('sap_body').innerHTML = '<div class="sap-card sap-empty"><span class="chip c-lock">Super Admin</span><br><br>The System Administrator has universal access to every page and action. It is never put on the panel and cannot be changed here.</div>'; bar(); return; }
        if (!a.on_panel) { STAFF = null; PENDING = {}; $('sap_who_wrap').innerHTML = whoCard({ emp_name: a.name, username: a.username, group_name: a.group, off: true }); $('sap_body').innerHTML = '<div class="sap-card sap-empty">This account is not on the panel, so it has no access at all. Bring it onto the panel from the Accounts &amp; assignments tab; it starts with nothing open.</div>'; bar(); return; }
        await openPerson(a.staff_id);
    }
    async function openPerson(staffId) {
        await ensureMap();
        const j = await api('/person?id=' + staffId);
        if (j.status !== 200) { Swal.fire({ icon: 'error', text: j.message || 'Could not load this person.' }); return; }
        STAFF = j.staff; PERMS = j.permissions || {}; LAST = j.last || {}; PENDING = {};
        if (!$('sap_q').value) $('sap_q').value = (STAFF.emp_name || STAFF.username) + ' (' + STAFF.username + ')';
        renderPerson(); document.dispatchEvent(new CustomEvent('sap:person', { detail: { staff: STAFF, perms: PERMS } }));
    }

    // ---------------------------------------------------------------- the person's identity, kept in view
    function whoCard(s) {
        const roles = (s.roles || []).map(r => '<span class="chip c-p">Role ' + r + ' · ' + esc(ROLE_NAMES[r] || '') + '</span>').join(' ');
        let state = '';
        if (s.admin) state = '<span class="chip c-lock">Super Admin · universal access</span>';
        else if (s.off) state = '<span class="chip c-none">Not on the panel · no access</span>';
        else {
            const acc = ACCOUNTS.find(a => a.staff_id === s.id) || {};
            state = (acc.enabled === false ? '<span class="chip c-block">Sign-in disabled</span>' : '<span class="chip c-open">Sign-in enabled</span>') + ' ' + (s.status === 'active' ? '<span class="chip c-open">Panel active</span>' : '<span class="chip c-part">Panel ' + esc(s.status) + '</span>');
        }
        let counts = '';
        if (!s.admin && !s.off && MAP) {
            const all = screens().filter(x => !x.protected); let o = 0, b = 0;
            all.forEach(x => { const st = now(x); if (st === 'open') o++; else if (st === 'blocked') b++; });
            counts = '<div class="sap-kpi"><span class="chip c-open">' + o + ' open</span><span class="chip c-block">' + b + ' blocked</span><span class="chip c-none">' + (all.length - o - b) + ' not set</span></div>';
        }
        return '<div class="sap-card sap-who" aria-live="polite"><span class="sap-av">' + esc(initials(s.emp_name || s.username)) + '</span><div><div class="nm">' + esc(s.emp_name || s.username) + '</div><div class="sub">Login <span class="url">' + esc(s.username) + '</span> · group ' + esc(s.group_name || '—') + ' (a group grants nothing)' + (s.department ? ' · ' + esc(s.department) : '') + '</div><div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">' + state + ' ' + (roles || (s.admin || s.off ? '' : '<span class="chip c-none">No payment role</span>')) + '</div></div><span class="sp"></span>' + counts + '</div>';
    }

    // ---------------------------------------------------------------- the person's access, by category and task
    function stateChip(list) {
        const sts = list.map(now); const o = sts.filter(x => x === 'open').length, b = sts.filter(x => x === 'blocked').length;
        if (!list.length) return '';
        if (o === list.length) return '<span class="chip c-open">All open</span>';
        if (o > 0) return '<span class="chip c-part">Partly open · ' + o + ' of ' + list.length + '</span>';
        if (b > 0) return '<span class="chip c-block">Blocked</span>';
        return '<span class="chip c-none">Not set · no access</span>';
    }
    function matches(s) {
        if (Q && !(s.name + ' ' + s.desc + ' ' + s.url).toLowerCase().includes(Q)) return false;
        if (FILTER === 'changed') return !!PENDING[s.id];
        return FILTER === 'all' || now(s) === FILTER;
    }
    function renderPerson() {
        $('sap_who_wrap').innerHTML = whoCard(STAFF);
        $('sap_mini').innerHTML = '<span class="sap-av">' + esc(initials(STAFF.emp_name || STAFF.username)) + '</span><b>' + esc(STAFF.emp_name || STAFF.username) + '</b> <code>' + esc(STAFF.username) + '</code>' + (Object.keys(PENDING).length ? ' · ' + Object.keys(PENDING).length + ' unsaved' : '');
        let html = '<div class="sap-tools"><input class="sap-inp" id="sap_fq" type="search" placeholder="Search a work description or URL" value="' + esc(Q) + '"><div class="sap-filter" id="sap_ff">' + ['all', 'open', 'blocked', 'unset', 'changed'].map(f => '<button type="button" data-f="' + f + '" aria-pressed="' + (FILTER === f) + '">' + (f === 'all' ? 'All' : f === 'unset' ? 'Not set' : f === 'changed' ? 'Changed' : LBL[f]) + '</button>').join('') + '</div><button class="gbtn" type="button" id="sap_expand">Expand all</button><button class="gbtn" type="button" id="sap_collapse">Collapse all</button></div>';
        MAP.forEach(c => {
            const cs = c.tasks.flatMap(t => t.screens).filter(s => !s.protected);
            const vis = c.tasks.map(t => ({ t, rows: t.screens.filter(matches) })).filter(x => x.rows.length);
            if (!vis.length && (Q || FILTER !== 'all')) return;
            const open = OPEN_STATE.cats[c.key] ?? (c.key !== 'shared');
            html += '<details class="sap-card sap-cat ' + (c.key === 'shared' ? 'shared' : '') + '" data-cat="' + c.key + '"' + (open || Q || FILTER !== 'all' ? ' open' : '') + '><summary><span class="tw-ar">▶</span><div><div class="t">' + esc(c.title) + '</div><div class="d">' + esc(c.desc) + '</div></div><span class="sp"></span>' + stateChip(cs) + (c.key !== 'shared' ? ' <button class="gbtn wh" type="button" data-g="open" data-scope="cat" data-k="' + c.key + '">Open all…</button><button class="gbtn wh" type="button" data-g="blocked" data-scope="cat" data-k="' + c.key + '">Block all…</button><button class="gbtn wh" type="button" data-g="unset" data-scope="cat" data-k="' + c.key + '">Remove all…</button>' : '') + '</summary>';
            vis.forEach(({ t, rows }) => {
                const tk = c.key + '.' + t.key, topen = OPEN_STATE.tasks[tk] ?? true, sw = t.screens.filter(s => !s.protected);
                html += '<details class="sap-task" data-task="' + tk + '"' + (topen || Q || FILTER !== 'all' ? ' open' : '') + '><summary><span class="tw-ar">▶</span><div><div class="t">' + esc(t.title) + '</div><div class="d">' + esc(t.desc) + '</div></div><span class="sp"></span>' + (t.locked ? '<span class="chip c-lock">Super Admin only · no switch</span>' : stateChip(sw) + ' <button class="gbtn o" type="button" data-g="open" data-scope="task" data-k="' + tk + '">Open all</button><button class="gbtn b" type="button" data-g="blocked" data-scope="task" data-k="' + tk + '">Block all</button><button class="gbtn n" type="button" data-g="unset" data-scope="task" data-k="' + tk + '">Remove all</button>') + '</summary>';
                html += '<table class="sap-t"><thead><tr><th style="width:36%">Work Description</th><th style="width:22%">URL</th><th style="width:24%">Access Control</th><th>Last Change</th></tr></thead><tbody>';
                rows.forEach(s => {
                    const st = now(s), p = PENDING[s.id], sv = saved(s), lp = PERMS[s.id], ll = LAST[s.id];
                    const tags = (s.role ? ' <span class="chip c-p" title="One person may hold only one of the three payment roles">Payment role ' + s.role + '</span>' : '') + (s.right ? ' <span class="chip c-none">Action</span>' : '') + (s.same_ids.length ? ' <span class="chip c-none" title="Several menu entries share this address; they switch together">Shared address</span>' : '');
                    const ctl = s.protected ? '<span class="chip c-lock">Super Admin only</span>' : '<div class="seg" role="radiogroup" aria-label="Access for ' + esc(s.name) + '">' + ['open', 'blocked', 'unset'].map(k => '<button type="button" role="radio" class="' + (k === 'open' ? 'o' : k === 'blocked' ? 'b' : 'n') + '" data-set="' + k + '" data-id="' + s.id + '" aria-pressed="' + (st === k) + '" aria-checked="' + (st === k) + '">' + (k === 'unset' ? 'Not set' : LBL[k]) + '</button>').join('') + '</div>' + (p ? '<div class="was">Was ' + LBL[sv] + ' · not saved yet</div>' : '');
                    const lc = lp ? '<b>' + esc(LBL[lp.state] || lp.state) + '</b> · ' + dt(lp.set_at) + ' · ' + esc(lp.set_by) + '<br><q>' + esc(lp.reason) + '</q>' : (ll ? '<b>' + esc(ll.action === 'removed' ? 'Removed' : ll.action) + '</b> · ' + dt(ll.at) + ' · ' + esc(ll.by_name || ll.by_user) + '<br><q>' + esc(ll.reason) + '</q>' : '—');
                    html += '<tr' + (p ? ' class="chg"' : '') + '><td class="wd" data-l="Work Description"><b>' + esc(s.desc) + tags + '</b><span>' + esc(s.name) + '</span></td><td data-l="URL"><span class="url">' + esc(s.url) + '</span></td><td data-l="Access Control">' + ctl + '</td><td class="lc" data-l="Last Change">' + lc + '</td></tr>';
                });
                html += '</tbody></table></details>';
            });
            html += '</details>';
        });
        $('sap_body').innerHTML = html || '<div class="sap-card sap-empty">Nothing matches.</div>';
        bar();
    }
    $('sap_body').addEventListener('input', e => { if (e.target.id === 'sap_fq') { Q = e.target.value.trim().toLowerCase(); const pos = e.target.selectionStart; renderPerson(); const f = $('sap_fq'); f.focus(); f.setSelectionRange(pos, pos); } });
    $('sap_body').addEventListener('toggle', e => { const d = e.target; if (d.dataset.cat) OPEN_STATE.cats[d.dataset.cat] = d.open; if (d.dataset.task) OPEN_STATE.tasks[d.dataset.task] = d.open; }, true);
    $('sap_body').addEventListener('click', e => {
        const f = e.target.closest('[data-f]'), s = e.target.closest('[data-set]'), g = e.target.closest('[data-g]');
        if (e.target.id === 'sap_expand' || e.target.id === 'sap_collapse') { const o = e.target.id === 'sap_expand'; MAP.forEach(c => { OPEN_STATE.cats[c.key] = o; c.tasks.forEach(t => OPEN_STATE.tasks[c.key + '.' + t.key] = o); }); renderPerson(); return; }
        if (f) { FILTER = f.dataset.f; renderPerson(); return; }
        if (s) { stage(+s.dataset.id, s.dataset.set, false); renderPerson(); return; }
        if (g) { e.preventDefault(); e.stopPropagation(); groupStage(g.dataset.scope, g.dataset.k, g.dataset.g); }
    });
    function stage(id, to, group) {
        const s = screens().find(x => x.id === id); if (!s || s.protected) return;
        if (saved(s) === to) delete PENDING[id]; else PENDING[id] = { to, group };
    }
    function groupStage(scope, key, to) {
        let list = scope === 'cat' ? MAP.find(c => c.key === key).tasks.flatMap(t => t.screens) : (() => { const [ck, tk] = key.split('.'); return MAP.find(c => c.key === ck).tasks.find(t => t.key === tk).screens; })();
        list = list.filter(s => !s.protected);
        const skipped = to === 'open' ? list.filter(s => s.role && now(s) !== 'open') : [];
        const affected = list.filter(s => now(s) !== to && !(to === 'open' && s.role));
        const title = scope === 'cat' ? MAP.find(c => c.key === key).title : (() => { const [ck, tk] = key.split('.'); return MAP.find(c => c.key === ck).tasks.find(t => t.key === tk).title; })();
        if (!affected.length) { Swal.fire({ icon: 'info', text: 'Nothing to change: every switchable row in "' + title + '" is already ' + LBL[to] + (skipped.length ? ' (payment roles are never opened from a heading)' : '') + '.' }); return; }
        const html = '<div style="text-align:left"><p>This will mark <b>' + affected.length + '</b> row(s) as <b>' + LBL[to] + '</b> (not saved until you review):</p><ul style="max-height:240px;overflow:auto">' + affected.map(s => '<li>' + esc(s.desc) + ' <span class="url" style="font-family:monospace;color:#842484">' + esc(s.url) + '</span></li>').join('') + '</ul>' + (skipped.length ? '<div style="background:#fdf4e3;border:1px solid #f2dfb5;border-radius:8px;padding:8px;margin-top:8px">Not included — payment roles are opened only on their own row:<br>' + skipped.map(s => esc(s.desc) + ' (role ' + s.role + ')').join('<br>') + '</div>' : '') + '</div>';
        Swal.fire({ title: LBL[to] + ' — ' + title, html, showCancelButton: true, confirmButtonText: 'Add to my changes', confirmButtonColor: '#842484' }).then(r => { if (!r.isConfirmed) return; affected.forEach(s => stage(s.id, to, true)); renderPerson(); });
    }

    // ---------------------------------------------------------------- pending changes → review → save
    function bar() { const n = Object.keys(PENDING).length; $('sap_bar').hidden = !STAFF || !n; $('sap_bar_t').textContent = n + ' change' + (n === 1 ? '' : 's') + ' not saved yet for ' + (STAFF ? (STAFF.emp_name || STAFF.username) : ''); }
    $('sap_discard').addEventListener('click', () => { PENDING = {}; renderPerson(); });
    $('sap_review').addEventListener('click', review);
    function review() {
        const all = screens(), ch = Object.keys(PENDING).map(id => ({ s: all.find(x => x.id === +id), p: PENDING[id] })).filter(x => x.s);
        const add = ch.filter(x => x.p.to === 'open'), blk = ch.filter(x => x.p.to === 'blocked'), rem = ch.filter(x => x.p.to === 'unset');
        const after = new Set(); all.forEach(s => { if (s.role && now(s) === 'open') after.add(s.role); });
        const catT = k => (MAP.find(c => c.key === k) || {}).title;
        const list = arr => '<ul>' + arr.map(x => '<li>' + esc(x.s.desc) + ' — <span class="url">' + esc(x.s.url) + '</span> <span style="color:#6b6272">(' + esc(catT(x.s.cat)) + ', was ' + LBL[saved(x.s)] + ')</span>' + (x.s.role ? ' <b>payment role ' + x.s.role + '</b>' : '') + '</li>').join('') + '</ul>';
        const m = document.createElement('div'); m.className = 'sap-modal'; m.setAttribute('role', 'dialog'); m.setAttribute('aria-modal', 'true');
        m.innerHTML = '<div class="box"><div class="hd"><h5>Review changes for ' + esc(STAFF.emp_name || STAFF.username) + ' (' + esc(STAFF.username) + ')</h5></div><div class="bd">'
            + (add.length ? '<h6 style="color:#5a9a2e">Additions — will be Open (' + add.length + ')</h6>' + list(add) : '')
            + (blk.length ? '<h6 style="color:#c0392b">Blocked (' + blk.length + ')</h6>' + list(blk) : '')
            + (rem.length ? '<h6 style="color:#6b6272">Removed — back to Not set, no access (' + rem.length + ')</h6>' + list(rem) : '')
            + (after.size > 1 ? '<div class="warn"><b>Payment-role separation:</b> after these changes this person would hold roles ' + [...after].join(' and ') + '. One person may hold only one of the three. The server will refuse the second role; keep them with different people.</div>' : '')
            + '<h6>Reason for these changes</h6><textarea id="sap_reason" placeholder="Explain the work responsibilities requiring this access (at least 5 characters)"></textarea><div class="err" id="sap_err" hidden></div></div>'
            + '<div class="ft"><button class="bq" type="button" data-x>Back</button><button class="bp" type="button" data-ok>Save ' + ch.length + ' change' + (ch.length === 1 ? '' : 's') + '</button></div></div>';
        document.body.appendChild(m); setTimeout(() => m.querySelector('#sap_reason').focus(), 50);
        const close = () => m.remove();
        m.addEventListener('click', e => { if (e.target === m || e.target.closest('[data-x]')) close(); });
        m.querySelector('[data-ok]').addEventListener('click', async ev => {
            const reason = m.querySelector('#sap_reason').value.trim(), err = m.querySelector('#sap_err');
            if (reason.length < 5) { err.textContent = 'A reason of at least 5 characters is required. Nothing has been changed.'; err.hidden = false; return; }
            ev.target.disabled = true; ev.target.textContent = 'Saving…';
            const j = await post('/apply', { staff_id: STAFF.id, reason, changes: ch.map(x => ({ menu_id: x.s.id, to: x.p.to, group: !!x.p.group })) });
            if (j.status !== 200) { err.textContent = j.message || 'Not saved.'; err.hidden = false; ev.target.disabled = false; ev.target.textContent = 'Save'; return; }
            close();
            const ref = (j.refused || []).map(r => { const s = all.find(x => x.id === r.menu_id); return '<li>' + esc(s ? s.desc : '#' + r.menu_id) + ': ' + esc(r.message) + '</li>'; }).join('');
            Swal.fire({ icon: ref ? 'warning' : 'success', title: j.message, html: ref ? '<div style="text-align:left">Refused by the server (nothing else was affected):<ul>' + ref + '</ul></div>' : '', confirmButtonColor: '#842484' });
            await loadAccounts(); await openPerson(STAFF.id);
        });
    }
    window.addEventListener('beforeunload', e => { if (Object.keys(PENDING).length) { e.preventDefault(); e.returnValue = ''; } });

    // ---------------------------------------------------------------- by category
    function renderCats() {
        ensureMap().then(() => {
            $('sap_cats').innerHTML = MAP.map(c => '<button type="button" data-c="' + c.key + '" aria-pressed="' + (CAT === c.key) + '">' + esc(c.title) + '</button>').join('');
            loadCat();
        });
    }
    $('sap_cats').addEventListener('click', e => { const b = e.target.closest('[data-c]'); if (!b) return; CAT = b.dataset.c; renderCats(); });
    async function loadCat() {
        $('sap_cat_body').innerHTML = '<div class="sap-card sap-empty">Loading…</div>';
        const j = await api('/category?cat=' + CAT); if (j.status !== 200) { $('sap_cat_body').innerHTML = '<div class="sap-card sap-empty">' + esc(j.message) + '</div>'; return; }
        const c = j.category, p = j.people;
        const card = (x, dim) => '<button type="button" class="sap-pc" data-staff="' + x.staff_id + '"' + (dim ? ' style="opacity:.75"' : '') + '><div class="h"><span class="sap-av">' + esc(initials(x.name || x.username)) + '</span><div><b>' + esc(x.name || x.username) + '</b><br><span class="url">' + esc(x.username) + '</span></div><span style="flex:1"></span>' + (x.coverage === 'every' ? '<span class="chip c-open">Every task</span>' : '<span class="chip c-part">Some tasks · ' + x.tasks.length + ' of ' + x.task_count + '</span>') + '</div>' + (dim ? '<div style="margin-bottom:6px"><span class="chip c-block">' + esc(x.state) + '</span></div>' : '') + '<div class="tl">' + x.tasks.map(t => '<span class="chip ' + (t.open === t.of ? 'c-open' : 'c-part') + '" title="' + t.open + ' of ' + t.of + ' screens open">' + esc(t.task) + ' · ' + t.open + '/' + t.of + '</span>').join('') + '</div></button>';
        let html = '<div class="sap-card" style="margin-bottom:14px"><div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap;padding:16px"><div class="sap-big">' + p.enabled.length + '</div><div style="flex:1;min-width:220px"><b>' + (p.enabled.length === 1 ? 'enabled person has' : 'enabled people have') + ' access to ' + esc(c.title) + '</b><div class="sap-note">' + esc(c.desc) + ' · ' + c.task_count + ' tasks · each person counted once, however many tasks they hold · people with permission, not people signed in</div></div></div>'
            + '<div class="sap-ppl">' + (p.enabled.map(x => card(x, false)).join('') || '<div class="sap-empty">No enabled staff member holds anything here.</div>') + '</div>';
        html += '<div class="sap-sec">Disabled or suspended accounts (' + p.inactive.length + ') — not counted above</div>' + (p.inactive.length ? '<div class="sap-ppl">' + p.inactive.map(x => card(x, true)).join('') + '</div>' : '<div class="sap-empty" style="padding:14px">None.</div>');
        html += '<div class="sap-sec">Super Admin — universal access, not counted above</div><div style="padding:14px;display:flex;gap:8px;flex-wrap:wrap">' + p.admins.map(a => '<span class="chip c-lock">' + esc(a.name || a.username) + ' · ' + esc(a.username) + '</span>').join('') + '</div></div>';
        $('sap_cat_body').innerHTML = html;
    }
    $('sap_cat_body').addEventListener('click', async e => { const b = e.target.closest('[data-staff]'); if (!b) return; mode('person'); $('sap_q').value = ''; await openPerson(+b.dataset.staff); window.scrollTo({ top: $('sap').getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' }); });

    // the selected person stays in view: a slim bar appears once their card scrolls away
    function mini() {
        const w = document.querySelector('#sap_who_wrap .sap-who'), vis = !$('v_perm').hidden && !$('sap_person').hidden && STAFF;
        $('sap_mini').hidden = !(vis && w && w.getBoundingClientRect().bottom < 70);
    }
    window.addEventListener('scroll', mini, { passive: true });

    // ---------------------------------------------------------------- shared with the Staff Assignment page
    window.SAP = {
        openPerson: async id => { await Promise.all([ensureMap(), ACCOUNTS.length ? null : loadAccounts()]); mode('person'); $('sap_q').value = ''; await openPerson(id); },
        refresh: async () => { await loadAccounts(); if (STAFF) await openPerson(STAFF.id); },
        staff: () => STAFF, perms: () => PERMS,
        search: q => { mode('person'); $('sap_q').value = q; renderResults(); }
    };
    Promise.all([ensureMap(), loadAccounts()]);
})();
</script>
