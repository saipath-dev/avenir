<?php
/**
 * tf-refresh-r1 (28 Oct 2026, Shantha) — what a browser refresh (F5, Ctrl+R, the reload button) keeps, on every TourFlow
 * screen (included by the shared reading layer, layouts/tf_ux, which the Tour Desk bar includes).
 *
 *   1. The record.     Screens put the open record in the address (?id=…, ?tour=…, ?hotel=…) with TfView.setParams(), so
 *                      a refresh opens the same one again — read fresh from the server. Back / Forward move between them.
 *   2. The place.      The scroll position is kept for this tab and this address (sessionStorage — every tab its own), and
 *                      put back once the page has drawn its content again; the reader's own scrolling stops it at once.
 *   3. The list.       Search boxes, filters and sort choices of a list (the usual ids: f_q, f_status, l_q, s_handler …,
 *                      or any field marked data-tf-keep) come back as they were, and the list is drawn with them.
 *   4. The tab.        A screen's own tab row ([data-tab], outside dialogs) comes back on the same tab, unless the address
 *                      names one. Folded sections are already kept by layouts/tf_ux.
 *   5. Unsaved typing. A field changed by the reader and not yet saved makes the browser ask before the page is left or
 *                      reloaded; nothing is ever saved by itself. A save that the server accepts counts as saved.
 *
 * Nothing here repeats an action: every TourFlow action is a background request (fetch / ajax POST), never a form
 * post, so a reload only reads. No addresses carry an action.
 */
?>
<script>
(function () {
    'use strict';
    if (window.TfView) return;
    var KEY_PREFIX = 'tfvs:';
    var store = {
        get: function (k) { try { return JSON.parse(sessionStorage.getItem(k) || 'null'); } catch (e) { return null; } },
        set: function (k, v) { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
    };
    function pageKey() { return KEY_PREFIX + location.pathname + location.search; }
    function listKey() { return KEY_PREFIX + 'list:' + location.pathname; }
    function tabKey() { return KEY_PREFIX + 'tab:' + location.pathname; }
    try { if ('scrollRestoration' in history) history.scrollRestoration = 'manual'; } catch (e) {}

    // ---------------------------------------------------------------- 1. the record in the address
    function setParams(params, opts) {
        opts = opts || {};
        var u = new URL(location.href);
        Object.keys(params).forEach(function (k) {
            var v = params[k];
            if (v === null || v === undefined || v === '' || v === false) u.searchParams.delete(k); else u.searchParams.set(k, String(v));
        });
        if (opts.hash !== undefined) u.hash = opts.hash || '';
        var next = u.pathname + (u.searchParams.toString() ? '?' + u.searchParams.toString() : '') + u.hash;
        var now = location.pathname + location.search + location.hash;
        if (next === now) return false;
        saveScroll();
        try { history[opts.push ? 'pushState' : 'replaceState'](Object.assign({ tfView: true }, opts.state || {}), '', next); } catch (e) { return false; }
        return true;
    }
    function param(name) { return new URL(location.href).searchParams.get(name); }

    // ---------------------------------------------------------------- 2. the place
    var restoring = null;
    function saveScroll() {
        var d = store.get(pageKey()) || {};
        d.y = Math.round(window.scrollY || window.pageYOffset || 0);
        d.at = Date.now();
        store.set(pageKey(), d);
    }
    var scrollTimer = null;
    window.addEventListener('scroll', function () {
        if (restoring) return;
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(saveScroll, 200);
    }, { passive: true });
    window.addEventListener('pagehide', function () { if (!restoring) saveScroll(); });

    function stopRestore() { if (restoring) { clearInterval(restoring.timer); restoring = null; } }
    ['wheel', 'touchstart', 'keydown', 'mousedown'].forEach(function (ev) {
        window.addEventListener(ev, function (e) { if (restoring && e.isTrusted) stopRestore(); }, { passive: true, capture: true });
    });
    /** Put the page back where this tab left it, once the content is long enough; keeps it there while the page settles. */
    function restoreScroll(force) {
        var d = store.get(pageKey());
        var y = d && typeof d.y === 'number' ? d.y : 0;
        if (!y && location.hash) {
            var el = document.getElementById(location.hash.slice(1));
            if (el) { el.scrollIntoView({ block: 'start' }); return; }
        }
        if (!y) return;
        stopRestore();
        var started = Date.now(), reached = 0, lastH = 0, stable = 0;
        restoring = { y: y, timer: setInterval(function () {
            var h = document.documentElement.scrollHeight;
            if (window.innerHeight > 0 && (h >= y + window.innerHeight * 0.4 || h - window.innerHeight >= y)) {
                var target = Math.max(0, Math.min(y, h - window.innerHeight));
                if (Math.abs(window.scrollY - target) > 4) window.scrollTo({ top: target, left: 0, behavior: 'instant' });   // never the page's smooth scrolling
                // done only once the page has really moved there (a tab that is not drawn ignores the scroll)
                if (!reached && Math.abs(window.scrollY - target) <= 4) reached = Date.now();
            }
            stable = (h === lastH) ? stable + 1 : 0; lastH = h;
            // done when it has been in place a while on a page that stopped growing, or after 12 seconds whatever happens
            if ((reached && Date.now() - reached > 1500 && stable >= 6) || Date.now() - started > 12000) { if (!reached) unreached = y; stopRestore(); }
        }, 120) };
    }
    // a page reloaded while its tab was in the background is not laid out yet: put it back when the tab is shown
    var unreached = 0;
    function retryUnreached() { if ((unreached || restoring) && Math.abs(window.scrollY) < 2) { unreached = 0; restoreScroll(); } }
    document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible') retryUnreached(); });
    window.addEventListener('focus', retryUnreached);
    window.addEventListener('pageshow', function (e) { if (e.persisted) restoreScroll(); });   // back from the browser's page cache

    // ---------------------------------------------------------------- 3. the list's search, filters and sort
    var KEEP_RE = /^(?:(?:f|l|s|aq|sm|t)_(?:q|status|handler|from|to|type|sort|stage|kind|state|class|cat|agent)|o_search|q_search|q_state|q_quick|q_alerts|search_[a-z_]+)$/;
    function keepable(el) {
        if (!el || !el.id || el.closest('.modal, .swal2-container, #page-topbar, header, .navbar-header, .app-search')) return false;
        if (!el.closest('.page-content, .main-content')) return false;   // only the screen's own fields, never the header's search
        if (el.hasAttribute('data-tf-nokeep')) return false;
        if (el.hasAttribute('data-tf-keep')) return true;
        if (!/^(INPUT|SELECT)$/.test(el.tagName)) return false;
        if (el.type === 'password' || el.type === 'file' || el.type === 'hidden') return false;
        return KEEP_RE.test(el.id) || el.type === 'search';
    }
    function keptFields() { return Array.prototype.filter.call(document.querySelectorAll('input[id], select[id]'), keepable); }
    function saveList() {
        var v = {};
        keptFields().forEach(function (el) { v[el.id] = (el.type === 'checkbox' || el.type === 'radio') ? (el.checked ? '1' : '') : el.value; });
        store.set(listKey(), v);
    }
    document.addEventListener('change', function (e) { if (keepable(e.target)) saveList(); }, true);
    document.addEventListener('input', function (e) { if (keepable(e.target)) saveList(); }, true);
    /** The kept filter values; a screen that reads its own filters at start can ask for them first (TfView.listValues()). */
    function listValues() { return store.get(listKey()) || {}; }
    /** Put the filters back into the fields and let the screen redraw its list (one change event per changed field). */
    function restoreList(fire) {
        var v = listValues(), changed = [];
        Object.keys(v).forEach(function (id) {
            var el = document.getElementById(id);
            if (!el || !keepable(el)) return;
            var now = (el.type === 'checkbox' || el.type === 'radio') ? (el.checked ? '1' : '') : el.value;
            if (now === v[id]) return;
            if (el.type === 'checkbox' || el.type === 'radio') el.checked = v[id] === '1'; else el.value = v[id];
            changed.push(el);
        });
        if (fire !== false && changed.length) {
            // each field its own event, as if it had been set by hand: screens that keep each filter in their own state see them all
            changed.forEach(function (el) {
                el.dispatchEvent(new Event(el.tagName === 'SELECT' || el.type === 'date' || el.type === 'checkbox' || el.type === 'radio' ? 'change' : 'input', { bubbles: true }));
            });
        }
        return changed.length;
    }

    // ---------------------------------------------------------------- 4. the screen's own tab row
    function tabEl(t) { return Array.prototype.filter.call(document.querySelectorAll('[data-tab]'), function (el) { return el.getAttribute('data-tab') === t && !el.closest('.modal, .swal2-container'); })[0] || null; }
    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('[data-tab]');
        if (b && e.isTrusted && !b.closest('.modal, .swal2-container')) store.set(tabKey(), b.getAttribute('data-tab'));
    }, true);
    function restoreTab() {
        if (param('tab')) return;
        var t = store.get(tabKey());
        if (!t) return;
        var el = tabEl(t);
        if (el && !el.classList.contains('on') && !el.classList.contains('active')) el.click();
    }

    // ---------------------------------------------------------------- 5. unsaved typing
    var dirty = new Set();
    function isEditable(el) {
        if (!el || !/^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName) || keepable(el)) return false;
        if (el.type === 'search' || el.type === 'file' || el.hasAttribute('data-tf-nodirty') || el.closest('[data-tf-nodirty], .swal2-container')) return false;
        return true;
    }
    function valueOf(el) { return (el.type === 'checkbox' || el.type === 'radio') ? String(el.checked) : String(el.value); }
    document.addEventListener('focusin', function (e) { var el = e.target; if (isEditable(el) && el.__tfOrig === undefined) el.__tfOrig = valueOf(el); }, true);
    function onEdit(e) {
        var el = e.target;
        if (!e.isTrusted || !isEditable(el)) return;
        if (el.__tfOrig === undefined) el.__tfOrig = el.defaultValue !== undefined && el.type !== 'checkbox' ? String(el.defaultValue) : '';
        dirty.add(el);
    }
    document.addEventListener('input', onEdit, true);
    document.addEventListener('change', onEdit, true);
    function pending() {
        var out = [];
        dirty.forEach(function (el) {
            if (!el.isConnected || el.offsetParent === null && !el.closest('.modal.show')) { dirty.delete(el); return; }
            if (valueOf(el) !== el.__tfOrig) out.push(el); else dirty.delete(el);
        });
        return out;
    }
    /** What was typed counts as saved: every field (no body given), or only the fields whose value went in that request. */
    function markSaved(body) {
        var text = null;
        if (body !== undefined && body !== null) {
            try {
                if (typeof body === 'string') text = body;
                else if (body instanceof FormData) { text = ''; body.forEach(function (v) { if (typeof v === 'string') text += '\n' + v; }); }
                else if (body instanceof URLSearchParams) text = body.toString();
            } catch (e) { text = null; }
        }
        dirty.forEach(function (el) {
            var v = valueOf(el);
            if (text !== null && v.trim() !== '' && text.indexOf(v.trim()) === -1 && text.indexOf(JSON.stringify(v.trim()).slice(1, -1)) === -1) return;   // not in this request
            el.__tfOrig = v;
            dirty.delete(el);
        });
    }
    // a save the server accepted: fetch and jQuery requests that change something
    if (window.fetch) {
        var nativeFetch = window.fetch;
        window.fetch = function (input, init) {
            var method = ((init && init.method) || (input && input.method) || 'GET').toUpperCase();
            var body = init && init.body !== undefined ? init.body : '';
            var p = nativeFetch.apply(this, arguments);
            if (method !== 'GET' && method !== 'HEAD') {
                p.then(function (r) {
                    if (!r || !r.ok) return;
                    r.clone().json().then(function (j) { if (!j || j.status === undefined || Number(j.status) === 200) markSaved(body); }).catch(function () { markSaved(body); });
                }).catch(function () {});
            }
            return p;
        };
    }
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery) {
            window.jQuery(document).ajaxSuccess(function (ev, xhr, s) { if (s && s.type && String(s.type).toUpperCase() !== 'GET') markSaved(s.data !== undefined && s.data !== null ? (typeof s.data === 'string' || s.data instanceof FormData ? s.data : JSON.stringify(s.data)) : ''); });
        }
    });
    // a dialog closed with Cancel / ×: what was typed in it was put aside on purpose
    document.addEventListener('hidden.bs.modal', function (e) { dirty.forEach(function (el) { if (e.target.contains(el)) dirty.delete(el); }); }, true);
    window.addEventListener('beforeunload', function (e) {
        if (!pending().length) return;
        e.preventDefault();
        e.returnValue = 'You have changes that are not saved.';
        return e.returnValue;
    });
    /** For a screen's own view changes (no page load): true when nothing unsaved would be lost, or the reader agrees. */
    function confirmLeave(what) {
        var p = pending();
        if (!p.length) return true;
        var ok = window.confirm('You have changes that are not saved' + (what ? ' on ' + what : '') + '. Leave them? They will be lost — nothing is saved by itself.');
        if (ok) { p.forEach(function (el) { el.__tfOrig = valueOf(el); }); dirty.clear(); }
        return ok;
    }

    // ---------------------------------------------------------------- start
    var started = false;
    function start() {
        if (started) return;
        started = true;
        // the screen's own start code has run (it sits at the end of the page); give it a moment to draw, then put things back
        setTimeout(function () { restoreList(true); restoreTab(); restoreScroll(); }, 30);
    }
    document.addEventListener('DOMContentLoaded', start);

    window.TfView = {
        setParams: setParams, param: param,
        saveScroll: saveScroll, restoreScroll: restoreScroll,
        listValues: listValues, restoreList: restoreList,
        pending: pending, markSaved: markSaved, confirmLeave: confirmLeave
    };
})();
</script>
