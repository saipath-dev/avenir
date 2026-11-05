<?php
/**
 * tf-search-r1 (22 Oct 2026) — the shared "Search pages…" box. One component, used beside Back / Home / Dashboard:
 *   <?= view('layouts/page_search') ?>
 * Lists only the pages the signed-in user may open (App\Libraries\PageSearch); opening one is an ordinary link that the
 * route filter checks again. It searches page NAMES only; the record searches on each page (tours, clients, …) are untouched.
 * Rendered once per page: a second call prints nothing.
 * tf-navload-r1 (22 Oct 2026): results are real links (click, Enter, Ctrl/middle-click for a new tab), and opening a page
 * shows a loading indicator at once — a gold progress bar under the header and an "Opening …" notice with the seconds
 * elapsed — also for the side menu and the Home desk lists. The indicator only shows; it blocks or changes nothing.
 */
if (defined('AV_PAGE_SEARCH_DONE') || !session()->get('user_id')) {
    return;
}
define('AV_PAGE_SEARCH_DONE', true);
$avpsMenu = (isset($allowed_menu_list) && isset($allowed_categories))
    ? ['allowed_menu_list' => $allowed_menu_list, 'allowed_categories' => $allowed_categories, 'user_role' => $user_role ?? session()->get('group_id')]
    : (static function () { try { return (new \App\Libraries\MenuLoader())->loadMenuData(); } catch (\Throwable $e) { return []; } })();
$avpsPages = $avpsMenu ? \App\Libraries\PageSearch::forUser($avpsMenu) : [];
?>
<style id="av-page-search-css">
.avps { position:relative; display:inline-flex; align-items:center; margin-left:4px; font-family:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
.avps-box { display:flex; align-items:center; gap:8px; height:38px; width:260px; padding:0 12px; border-radius:10px; background:#fff; border:1.5px solid rgba(132,36,132,.22);
  box-shadow:0 2px 8px rgba(0,0,0,.06); transition:border-color .15s, box-shadow .15s, width .2s; }
.avps-box:focus-within { border-color:#842484; box-shadow:0 0 0 .2rem rgba(132,36,132,.14); width:320px; }
.avps-box i { color:#842484; font-size:15px; line-height:1; }
.avps-box input { flex:1; min-width:0; border:0; outline:0; background:transparent; font-size:13.5px; font-weight:500; color:#2b2b33; letter-spacing:.01em; }
.avps-box input::placeholder { color:#8a8490; font-weight:400; }
.avps-box kbd { font:600 10.5px/1 "Inter Tight",system-ui,sans-serif; color:#6b6f7b; background:#f6f4f2; border:1px solid #e8e3df; border-radius:5px; padding:3px 5px; box-shadow:none; }
.avps-toggle { display:none; width:42px; height:42px; border:0; border-radius:10px; background:#fff; color:#842484; box-shadow:0 2px 8px rgba(0,0,0,.12); border:1.5px solid rgba(132,36,132,.22); }
.avps-list { position:absolute; top:calc(100% + 6px); left:0; width:min(420px, 92vw); max-height:60vh; overflow:auto; z-index:2000; margin:0; padding:6px; list-style:none;
  background:#fff; border:1px solid #e8e3df; border-radius:12px; box-shadow:0 14px 36px rgba(0,0,0,.18); }
.avps-list[hidden] { display:none; }
.avps-item { display:flex; align-items:center; gap:12px; padding:9px 11px; border-radius:9px; cursor:pointer; color:#2b2b33; text-decoration:none !important; }
.avps-item.busy { cursor:progress; }
.avps-item.busy small { background:#fff; color:#842484; }
.avps-item .avps-spin { flex:none; width:16px; height:16px; border:2px solid rgba(132,36,132,.30); border-top-color:#842484; border-radius:50%; animation:avps-rot .7s linear infinite; }
@keyframes avps-rot { to { transform:rotate(360deg); } }
/* tf-navload-r1: the page-opening indicator */
#avnl-bar { position:fixed; left:0; top:0; height:3px; width:0; z-index:3000; background:linear-gradient(90deg,#e9c46a,#d9a441); box-shadow:0 0 8px rgba(233,196,106,.7); opacity:0; pointer-events:none; }
#avnl-bar.on { opacity:1; width:82%; transition:width 12s cubic-bezier(.08,.6,.2,1), opacity .2s; }
#avnl-note { position:fixed; left:50%; bottom:22px; transform:translate(-50%,16px); z-index:3001; display:flex; align-items:center; gap:12px; max-width:min(560px,92vw);
  padding:12px 18px 12px 14px; border-radius:12px; background:#842484; color:#fff; font:500 14px/1.35 "Inter Tight","Inter","Segoe UI",system-ui,sans-serif;
  box-shadow:0 14px 34px rgba(60,10,25,.35); opacity:0; pointer-events:none; transition:opacity .15s, transform .15s; }
#avnl-note.on { opacity:1; transform:translate(-50%,0); }
#avnl-note .sp { flex:none; width:20px; height:20px; border:2.5px solid rgba(255,255,255,.35); border-top-color:#e9c46a; border-radius:50%; animation:avps-rot .7s linear infinite; }
#avnl-note b { font-weight:700; }
#avnl-note small { display:block; color:rgba(255,255,255,.80); font-size:12px; font-weight:400; }
html.avnl-busy, html.avnl-busy * { cursor:progress !important; }
@media (prefers-reduced-motion:reduce) { #avnl-bar.on { transition:none; width:60%; } .avps-item .avps-spin, #avnl-note .sp { animation-duration:2s; } }
@media print { #avnl-bar, #avnl-note { display:none !important; } }
.avps-item b { font-weight:600; font-size:13.5px; flex:1; min-width:0; }
.avps-item > i { font-size:16px; color:#842484; width:18px; text-align:center; flex:none; }
.avps-item[aria-selected="true"] > i, .avps-item:hover > i { color:#842484; }
.avps-item mark { background:#fcf3de; color:inherit; padding:0 1px; border-radius:3px; }
.avps-item small { flex:none; font-size:11px; font-weight:600; color:#78b848; background:rgba(120,184,72,.18); border-radius:20px; padding:2px 8px; white-space:nowrap; }
.avps-item[aria-selected="true"], .avps-item:hover { background:rgba(132,36,132,.12); color:#842484; }
.avps-item[aria-selected="true"] small, .avps-item:hover small { background:#fff; color:#842484; }
.avps-item[aria-selected="true"] mark, .avps-item:hover mark { background:#fbe3a6; }
.avps-count { padding:4px 11px 6px; font-size:11px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:#6b6f7b; }
.avps-empty { padding:10px 12px; color:#6b6f7b; font-size:13px; }
@media (max-width:1199px) { .avps-box { width:200px; } .avps-box:focus-within { width:260px; } .avps-box kbd { display:none; } }
@media (max-width:767px) {
  .avps-toggle { display:inline-grid; place-items:center; }
  .avps-box { display:none; }
  .avps.open .avps-box { display:flex; position:fixed; left:12px; right:12px; top:64px; width:auto; height:46px; z-index:2001; box-shadow:0 8px 24px rgba(0,0,0,.18); }
  .avps.open .avps-list { position:fixed; left:12px; right:12px; top:116px; width:auto; }
}
@media print { .avps { display:none !important; } }
</style>
<div class="avps" id="avps" role="search">
    <button type="button" class="avps-toggle" id="avps-toggle" aria-label="Search pages" title="Search pages"><i class="bi bi-search"></i></button>
    <div class="avps-box">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="avps-input" placeholder="Search pages…" autocomplete="off" spellcheck="false"
               role="combobox" aria-expanded="false" aria-controls="avps-list" aria-autocomplete="list" aria-label="Search pages">
        <kbd title="Press / to search pages">/</kbd>
    </div>
    <ul class="avps-list" id="avps-list" role="listbox" hidden></ul>
</div>
<div id="avnl-bar" aria-hidden="true"></div>
<div id="avnl-note" role="status" aria-live="polite"><span class="sp" aria-hidden="true"></span><span><span class="t"></span><small class="s"></small></span></div>
<script>
(function () {
    'use strict';
    const PAGES = <?= json_encode($avpsPages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    const BASE = <?= json_encode(rtrim(base_url(), '/') . '/', JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>;
    const root = document.getElementById('avps'), input = document.getElementById('avps-input'), list = document.getElementById('avps-list'), toggle = document.getElementById('avps-toggle');
    if (!root || !input) return;
    const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const norm = s => String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    let hits = [], sel = -1;
    function mark(label, words) {   // highlight on the raw text, escape every piece
        const ws = words.filter(Boolean).map(w => w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
        if (!ws.length) return esc(label);
        return String(label).split(new RegExp('(' + ws.join('|') + ')', 'ig')).map((part, i) => i % 2 ? '<mark>' + esc(part) + '</mark>' : esc(part)).join('');
    }
    function icon(p) {   // a quiet icon by what the page is; decoration only
        const t = norm(p.l + ' ' + p.a);
        if (/dashboard|pilot|home/.test(t)) return 'bi-bar-chart-fill';
        if (/payment|payable|voucher|bank|cash|desk/.test(t)) return 'bi-credit-card-2-front';
        if (/report|ledger|balance|statement/.test(t)) return 'bi-file-earmark-text';
        if (/hotel|room|booking|reservation/.test(t)) return 'bi-building';
        if (/transport|driver|vehicle|crew|mileage/.test(t)) return 'bi-truck';
        if (/inquir|tour|quotation/.test(t)) return 'bi-map';
        if (/setting|privilege|user|group|menu/.test(t)) return 'bi-gear';
        return 'bi-file-earmark';
    }
    function search(q) {
        const words = norm(q).trim().split(/\s+/).filter(Boolean);
        if (!words.length) return [];
        return PAGES.map(p => {
            const l = norm(p.l), a = norm(p.a);
            if (!words.every(w => l.includes(w) || a.includes(w))) return null;
            let score = 0; words.forEach(w => { if (l.startsWith(w)) score += 3; else if (new RegExp('\\b' + w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).test(l)) score += 2; else if (l.includes(w)) score += 1; });
            if (p.k) score += 2;   // a desk's main page
            return { p, score };
        }).filter(Boolean).sort((x, y) => y.score - x.score || x.p.l.length - y.p.l.length || x.p.l.localeCompare(y.p.l)).slice(0, 40).map(x => x.p);
    }
    function render() {
        const q = input.value;
        hits = search(q); sel = hits.length ? 0 : -1;
        if (!q.trim()) { close(); return; }
        const words = norm(q).trim().split(/\s+/);
        list.innerHTML = hits.length
            ? `<li class="avps-count" role="presentation">${hits.length} page${hits.length === 1 ? '' : 's'} you can open</li>` + hits.map((p, i) => `<li role="presentation"><a class="avps-item" role="option" id="avps-o${i}" data-i="${i}" href="${esc(BASE + p.u)}" tabindex="-1" aria-selected="${i === sel}"><i class="bi ${icon(p)}" aria-hidden="true"></i><b>${mark(p.l, words)}</b><small>${esc(p.a)}</small></a></li>`).join('')
            : '<li class="avps-empty" role="option" aria-disabled="true">No page you can open matches “' + esc(q) + '”</li>';
        list.hidden = false; input.setAttribute('aria-expanded', 'true');
        if (getComputedStyle(list).position === 'absolute') {   // keep the list on screen when the box sits at the right edge
            list.style.left = ''; list.style.right = '';
            if (list.getBoundingClientRect().right > window.innerWidth - 8) { list.style.left = 'auto'; list.style.right = '0'; }
        }
        input.setAttribute('aria-activedescendant', sel >= 0 ? 'avps-o' + sel : '');
    }
    function move(d) {
        if (!hits.length) return;
        sel = (sel + d + hits.length) % hits.length;
        list.querySelectorAll('.avps-item').forEach((li, i) => li.setAttribute('aria-selected', i === sel ? 'true' : 'false'));
        const cur = document.getElementById('avps-o' + sel); if (cur) cur.scrollIntoView({ block: 'nearest' });
        input.setAttribute('aria-activedescendant', 'avps-o' + sel);
    }
    function go(i) {   // keyboard (Enter) and plain clicks: show the indicator at once, then open the page
        const p = hits[i]; if (!p) return;
        const li = document.getElementById('avps-o' + i);
        if (li && !li.classList.contains('busy')) { li.classList.add('busy'); const ic = li.querySelector('i'); if (ic) ic.outerHTML = '<span class="avps-spin" aria-hidden="true"></span>'; }
        window.avNavLoading(p.l, BASE + p.u);
        if (busyTo === BASE + p.u) return;   // already opening this page: a second press would only queue a second request
        busyTo = BASE + p.u;
        setTimeout(() => window.location.assign(BASE + p.u), 30);   // let the indicator paint first
    }
    let busyTo = '';
    function close() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); input.removeAttribute('aria-activedescendant'); }
    input.addEventListener('input', render);
    input.addEventListener('focus', () => { if (input.value.trim()) render(); });
    input.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown') { e.preventDefault(); if (list.hidden) render(); else move(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
        else if (e.key === 'Enter') { e.preventDefault(); if (sel >= 0) go(sel); }
        else if (e.key === 'Escape') { close(); root.classList.remove('open'); input.blur(); }
    });
    list.addEventListener('mousedown', e => { if (e.target.closest('.avps-item')) e.preventDefault(); });   // keep the focus in the box; the click opens the page
    list.addEventListener('click', e => {
        const a = e.target.closest('.avps-item'); if (!a) return;
        if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;   // new tab / window: the browser's own behaviour
        e.preventDefault(); go(Number(a.dataset.i));
    });
    document.addEventListener('click', e => { if (!root.contains(e.target)) { close(); root.classList.remove('open'); } });
    toggle.addEventListener('click', e => { e.stopPropagation(); root.classList.toggle('open'); if (root.classList.contains('open')) input.focus(); });
    document.addEventListener('keydown', e => {   // "/" jumps to the page search unless the user is typing somewhere
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
        const t = e.target; if (t && (t.isContentEditable || /^(input|textarea|select)$/i.test(t.tagName))) return;
        e.preventDefault(); root.classList.add('open'); input.focus();
    });
})();
(function () {   // tf-navload-r1: the page-opening indicator, shared by the search, the side menu and the Home desk lists
    'use strict';
    const bar = document.getElementById('avnl-bar'), note = document.getElementById('avnl-note');
    if (!bar || !note) return;
    let t0 = 0, tick = null;
    function hide() { clearInterval(tick); tick = null; bar.classList.remove('on'); note.classList.remove('on'); document.documentElement.classList.remove('avnl-busy');
        document.querySelectorAll('.avps-item.busy').forEach(a => a.classList.remove('busy')); }
    window.avNavLoading = function (label, href) {
        note.querySelector('.t').innerHTML = 'Opening <b></b>…'; note.querySelector('.t b').textContent = label || 'page';
        note.querySelector('.s').textContent = '';
        document.documentElement.classList.add('avnl-busy');
        bar.classList.remove('on'); void bar.offsetWidth; bar.classList.add('on'); note.classList.add('on');
        t0 = Date.now(); clearInterval(tick);
        tick = setInterval(() => {
            const s = Math.round((Date.now() - t0) / 1000);
            if (s >= 3) note.querySelector('.s').textContent = 'Still preparing the page · ' + s + ' s — please wait, there is no need to click again';
        }, 1000);
    };
    window.addEventListener('pageshow', e => { if (e.persisted) hide(); });   // back / forward from the cache
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && tick) hide(); });
    // plain left-clicks on page links in the side menu, the Home desk lists and the Home link cards
    document.addEventListener('click', e => {
        if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest('#navbar-nav a[href], .navbar-menu a[href], .hd-menu a[href], .hd-links a[href]');
        if (!a || a.target && a.target !== '_self' || a.hasAttribute('download') || a.hasAttribute('data-bs-toggle')) return;
        const h = a.getAttribute('href') || '';
        if (!h || h.charAt(0) === '#' || /^(javascript|mailto|tel):/i.test(h)) return;
        let u; try { u = new URL(a.href, location.href); } catch (err) { return; }
        if (u.origin !== location.origin || (u.pathname === location.pathname && u.search === location.search && u.hash)) return;
        window.avNavLoading(a.textContent.trim().replace(/\s+/g, ' '), u.href);
    });
})();
</script>
