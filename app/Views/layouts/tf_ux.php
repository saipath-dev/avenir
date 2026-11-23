<?php
/**
 * The new-generation reading layer, shared by every TourFlow screen (included by the Tour Desk bar):
 *
 *   1. Leads fold.   The page lead (.tf-title .sub) shows its first sentence; the rest opens under "How this works".
 *   2. Help folds.   A long explanatory paragraph (.help over ~230 characters, not a field hint) shows its first
 *                    sentence with "more"; the short hints stay as they are.
 *   3. A section rail. A screen with five or more sections (.tf-card-h h6) gets a compact rail of them — in the
 *                    sticky side column when the page has one, else at the right edge of a wide window — the
 *                    current section lit, each one a jump; a section's title folds its body and the fold is
 *                    remembered for the screen.
 *
 * Nothing is removed from the page: the text is all still there for the reader who opens it, for printing, and for
 * the screen tests that read it. Dynamic screens re-render their cards; the layer watches for that and applies itself
 * again (idempotent: every element it has handled is marked).
 */
?>
<style>
    .tf-fold { display:inline; }
    .tf-fold .tf-fold-rest { display:none; }
    .tf-fold.open .tf-fold-rest { display:inline; }
    .tf-fold-toggle { display:inline-block; margin-left:6px; font-size:11.5px; font-weight:600; color:#842484; cursor:pointer; white-space:nowrap; text-decoration:none; }
    .tf-fold-toggle:hover { text-decoration:underline; }
    .tf-fold-toggle i { font-size:10px; transition:transform .15s ease; }
    .tf-fold.open .tf-fold-toggle i { transform:rotate(180deg); }
    .tf-title .sub .tf-fold-toggle { color:#842484; }
    .tf-rail { font-size:12px; border:1px solid var(--tf-line, #dfe5ec); border-radius:10px; background:#fff; padding:8px 6px; box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf-rail .tf-rail-h { font-size:10.5px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted, #5f6f80); padding:2px 8px 6px; font-weight:600; }
    .tf-rail a { display:flex; align-items:center; gap:7px; padding:5px 8px; border-radius:7px; color:var(--tf-ink, #1d2733); text-decoration:none; line-height:1.25; }
    .tf-rail a .dot { width:6px; height:6px; border-radius:50%; background:#cfd6de; flex:none; }
    .tf-rail a:hover { background:#f4f6f9; }
    .tf-rail a.on { background:rgba(132,36,132,.08); color:#842484; font-weight:600; }
    .tf-rail a.on .dot { background:#842484; }
    .tf-rail a.folded { color:var(--tf-muted, #5f6f80); }
    .tf-rail a.folded .dot { background:transparent; border:1px solid #cfd6de; }
    .tf-rail-float { position:fixed; right:18px; top:120px; width:190px; z-index:20; max-height:calc(100vh - 150px); overflow:auto; }
    @media (max-width:1380px) { .tf-rail-float { display:none; } }
    .tf-card-h h6.tf-foldable { cursor:pointer; user-select:none; }
    .tf-card-h h6.tf-foldable::before { content:"\25BE"; display:inline-block; width:12px; font-size:11px; color:#aab4bf; margin-right:4px; transition:transform .15s ease; }
    .tf-card.tf-folded > .tf-card-h h6.tf-foldable::before { transform:rotate(-90deg); }
    .tf-card.tf-folded > .tf-card-h { border-bottom:0; }
    .tf-card.tf-folded > :not(.tf-card-h) { display:none !important; }
    @media print { .tf-fold .tf-fold-rest { display:inline; } .tf-fold-toggle, .tf-rail { display:none; } .tf-card.tf-folded > :not(.tf-card-h) { display:block !important; } }
</style>
<script>
(function () {
    'use strict';
    var ROOT = document.querySelector('.tf') || document.body;
    var PAGE = (location.pathname || '').replace(/\/+$/, '');
    var MIN_LEAD = 170, MIN_HELP = 230;

    function firstSentence(text) {
        // the first sentence, never shorter than a few words; a colon or dash before the first stop keeps the stop
        var m = /^(.{25,}?[.!?])(\s|$)/.exec(text);
        return m ? m[1] : null;
    }
    function fold(el, label) {
        if (el.dataset.tfFold) return;
        var text = (el.textContent || '').replace(/\s+/g, ' ').trim();
        var head = firstSentence(text);
        if (!head || text.length - head.length < 40) { el.dataset.tfFold = 'short'; return; }
        // split the element's own nodes at the end of the first sentence, keeping every tag, link and pill as it is
        el.dataset.tfFold = '1';
        var wrap = document.createElement('span'); wrap.className = 'tf-fold';
        var headSpan = document.createElement('span'); headSpan.className = 'tf-fold-head';
        var restSpan = document.createElement('span'); restSpan.className = 'tf-fold-rest';
        var need = head.length, got = 0, nodes = Array.prototype.slice.call(el.childNodes), i, n;
        for (i = 0; i < nodes.length; i++) {
            n = nodes[i];
            if (got >= need) { restSpan.appendChild(n); continue; }
            var t = (n.textContent || '').replace(/\s+/g, ' ');
            if (got + t.length <= need || n.nodeType !== 3) { headSpan.appendChild(n); got += t.length; continue; }
            // a text node that crosses the boundary: cut it
            var raw = n.nodeValue || '', k = 0, seen = 0;
            while (k < raw.length && seen < need - got) { if (!(/\s/.test(raw[k]) && k > 0 && /\s/.test(raw[k - 1]))) seen++; k++; }
            if (n.parentNode) n.parentNode.removeChild(n);
            headSpan.appendChild(document.createTextNode(raw.slice(0, k)));
            restSpan.appendChild(document.createTextNode(raw.slice(k)));
            got = need;
        }
        var tog = document.createElement('a'); tog.className = 'tf-fold-toggle'; tog.href = '#'; tog.setAttribute('role', 'button'); tog.setAttribute('aria-expanded', 'false');
        tog.innerHTML = label + ' <i class="bi bi-chevron-down"></i>';
        wrap.appendChild(headSpan); wrap.appendChild(restSpan); wrap.appendChild(tog);
        el.appendChild(wrap);
        tog.addEventListener('click', function (e) {
            e.preventDefault(); e.stopPropagation();
            var open = wrap.classList.toggle('open');
            tog.setAttribute('aria-expanded', open ? 'true' : 'false');
            tog.innerHTML = (open ? 'Less' : label) + ' <i class="bi bi-chevron-down"></i>';
        });
    }
    function foldLeads() {
        ROOT.querySelectorAll('.tf-title .sub, .hw-head .sub, .hw-title .sub, .page-title-box .sub, .hr-title .sub').forEach(function (el) {
            if ((el.textContent || '').trim().length >= MIN_LEAD) fold(el, 'How this works');
        });
    }
    function foldHelp() {
        ROOT.querySelectorAll('.help, .dtl').forEach(function (el) {
            if (el.dataset.tfFold) return;
            if (el.closest('.tf-card-h, th, label, .modal, .swal2-container, .tf-rail, button, a')) return;
            if (el.querySelector('input, select, textarea, button, table')) return;
            if ((el.textContent || '').trim().length >= MIN_HELP) fold(el, 'more');
        });
    }

    // ---- the section rail and the folding cards ----
    var railHost = null, rail = null, foldKey = 'tf-fold:' + PAGE;
    function foldedSet() { try { return JSON.parse(sessionStorage.getItem(foldKey) || '[]'); } catch (e) { return []; } }
    function remember(title, folded) {
        try { var s = foldedSet().filter(function (t) { return t !== title; }); if (folded) s.push(title); sessionStorage.setItem(foldKey, JSON.stringify(s)); } catch (e) {}
    }
    function sections() {
        var out = [];
        ROOT.querySelectorAll('.tf-card > .tf-card-h > h6').forEach(function (h) {
            var card = h.closest('.tf-card');
            if (!card || card.closest('.modal, .tf-rail, .sidecol') || card.offsetParent === null) return;
            var title = (h.textContent || '').replace(/\s+/g, ' ').trim();
            if (!title) return;
            out.push({ h: h, card: card, title: title });
        });
        return out;
    }
    function buildRail() {
        var secs = sections();
        var old = ROOT.querySelector('.tf-rail'); if (old) old.remove();
        rail = null;
        if (secs.length < 5) return;
        var folded = foldedSet();
        rail = document.createElement('nav'); rail.className = 'tf-rail'; rail.setAttribute('aria-label', 'Sections');
        rail.innerHTML = '<div class="tf-rail-h">On this screen</div>';
        secs.forEach(function (s, i) {
            if (!s.card.id) s.card.id = 'tfsec-' + i;
            var a = document.createElement('a'); a.href = '#' + s.card.id; a.innerHTML = '<span class="dot"></span><span>' + s.title.replace(/</g, '&lt;') + '</span>';
            a.addEventListener('click', function (e) { e.preventDefault(); if (s.card.classList.contains('tf-folded')) toggleCard(s, false); s.card.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
            s.a = a; rail.appendChild(a);
            if (!s.h.classList.contains('tf-foldable')) {
                s.h.classList.add('tf-foldable'); s.h.title = 'Fold or open this section';
                s.h.addEventListener('click', function (e) { if (e.target.closest('a, button, input, select')) return; toggleCard(s); });
            }
            if (folded.indexOf(s.title) >= 0) toggleCard(s, true, true);
        });
        var side = ROOT.querySelector('.sidecol');
        if (side && side.offsetParent !== null) { side.insertBefore(rail, side.firstChild); }
        else { rail.classList.add('tf-rail-float'); ROOT.appendChild(rail); }
        watch(secs);
    }
    function toggleCard(s, force, silent) {
        var folded = typeof force === 'boolean' ? force : !s.card.classList.contains('tf-folded');
        s.card.classList.toggle('tf-folded', folded);
        if (s.a) s.a.classList.toggle('folded', folded);
        if (!silent) remember(s.title, folded);
    }
    var io = null;
    function watch(secs) {
        if (io) io.disconnect();
        if (!('IntersectionObserver' in window)) return;
        io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                var s = secs.filter(function (x) { return x.card === en.target; })[0];
                if (s && s.a) s.a.classList.toggle('on', en.isIntersecting && en.intersectionRatio > 0);
            });
        }, { rootMargin: '-90px 0px -60% 0px', threshold: [0, 0.01] });
        secs.forEach(function (s) { io.observe(s.card); });
    }

    var pending = null, bound = [];
    function apply() {
        pending = null;
        foldLeads(); foldHelp();
        var secs = sections(), i;
        // rebuild when the rail is missing, the sections changed, or a screen re-rendered its cards (the rail would then point at
        // elements no longer on the page, its jumps and folds dead) — the same titles, new elements, so bind again
        var stale;
        if (secs.length < 5) { stale = !!rail; }                       // too few sections: a rail, if one is left, goes
        else {
            stale = !rail || !rail.isConnected || secs.length !== bound.length;
            for (i = 0; !stale && i < secs.length; i++) { if (bound[i] !== secs[i].card || !secs[i].h.classList.contains('tf-foldable')) stale = true; }
        }
        if (stale) { buildRail(); bound = rail ? secs.map(function (s) { return s.card; }) : []; }
    }
    function schedule() { if (pending) return; pending = setTimeout(apply, 120); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply); else apply();
    try {
        new MutationObserver(function (muts) {
            for (var i = 0; i < muts.length; i++) {
                var t = muts[i].target;
                if (t.closest && t.closest('.tf-rail, .tf-fold')) continue;
                schedule(); return;
            }
        }).observe(ROOT, { childList: true, subtree: true });
    } catch (e) {}
})();
</script>
<?= view('layouts/tf_viewstate') ?>
