/*
 * tf-help-r1 — the "User Manual" button and help panel, on every screen of Avenir.
 * Plain JavaScript, no other library. It only reads (GET help/page, help/ask, help/topic); the links
 * it shows are ordinary links to screens. Opening help or asking a question changes nothing.
 */
(function () {
    'use strict';
    var me = document.currentScript || document.querySelector('script[src*="assets/help/help-widget.js"]');
    if (!me || document.getElementById('avh-root')) { return; }
    var BASE = me.getAttribute('data-base') || '/';
    var PATH = me.getAttribute('data-path') || '';
    var MODE = me.getAttribute('data-mode') || 'float';
    if (BASE.slice(-1) !== '/') { BASE += '/'; }

    var ICON_BOOK = '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M5 3h11a3 3 0 0 1 3 3v14a1 1 0 0 1-1 1H6a3 3 0 0 1-3-3V5a2 2 0 0 1 2-2zm0 2v11.17A3 3 0 0 1 6 16h11V6a1 1 0 0 0-1-1H5zm1 13a1 1 0 0 0 0 2h11v-2H6zm2-10h6v2H8V8zm0 3h6v2H8v-2z"/></svg>';
    var ICON_LOCK = '<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a5 5 0 0 1 5 5v3h1a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h1V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v3h6V7a3 3 0 0 0-3-3z"/></svg>';

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }
    function el(tag, cls, html) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (html != null) { e.innerHTML = html; } return e; }
    function niceDate(ymd) {
        var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(ymd || ''); if (!m) { return ymd || ''; }
        return Number(m[3]) + ' ' + ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][Number(m[2]) - 1] + ' ' + m[1];
    }

    // ---- build ----
    var root = el('div'); root.id = 'avh-root'; root.className = MODE === 'page' ? 'avh-pagemode' : '';
    var fab = el('button', 'avh-fab', ICON_BOOK + '<span>User Manual</span>');
    fab.type = 'button'; fab.setAttribute('aria-haspopup', 'dialog'); fab.setAttribute('aria-controls', 'avh-panel');
    fab.title = 'User Manual — ask how to do something on this screen';
    var panel = el('aside', 'avh-panel');
    panel.id = 'avh-panel'; panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-label', 'User Manual'); panel.hidden = MODE !== 'page';
    panel.innerHTML =
        '<div class="avh-head"><div class="avh-title">' + ICON_BOOK + '<span>User Manual</span></div><div class="avh-sub" id="avh-sub"></div>' +
        (MODE === 'page' ? '' : '<button type="button" class="avh-x" aria-label="Close the User Manual" title="Close (Esc)">&times;</button>') + '</div>' +
        '<form class="avh-ask" autocomplete="off"><label class="avh-sr" for="avh-q">Ask a question</label>' +
        '<input id="avh-q" type="search" maxlength="300" placeholder="Ask a question in your own words">' +
        '<button type="submit">Ask</button></form>' +
        '<div class="avh-body" id="avh-body" aria-live="polite"><div class="avh-muted">Loading…</div></div>' +
        '<div class="avh-foot" id="avh-foot"></div>';
    if (MODE !== 'page') { root.appendChild(fab); }
    root.appendChild(panel);
    document.body.appendChild(root);

    var body = panel.querySelector('#avh-body');
    var input = panel.querySelector('#avh-q');
    var home = null;       // the data of help/page, loaded once
    var lastFocus = null;

    function get(url) {
        return fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function (r) {
                var t = r.headers.get('Content-Type') || '';
                if (t.indexOf('application/json') === -1) { throw new Error('session'); }
                return r.json();
            });
    }
    function failed(e) {
        body.innerHTML = '<div class="avh-card avh-warn">' + (e && e.message === 'session'
            ? 'Your session has ended. Please log in again, then open the User Manual.'
            : 'The User Manual could not be reached just now. Please try again in a moment.') + '</div>';
    }

    // ---- open / close ----
    function open() {
        if (MODE === 'page') { return; }
        lastFocus = document.activeElement;
        panel.hidden = false; root.classList.add('avh-open'); fab.setAttribute('aria-expanded', 'true');
        if (!home) { loadHome(); }
        setTimeout(function () { input.focus(); }, 30);
    }
    function close() {
        if (MODE === 'page') { return; }
        panel.hidden = true; root.classList.remove('avh-open'); fab.setAttribute('aria-expanded', 'false');
        if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) { /* ignore */ } }
    }
    fab.addEventListener('click', function () { if (panel.hidden) { open(); } else { close(); } });
    var x = panel.querySelector('.avh-x'); if (x) { x.addEventListener('click', close); }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden && MODE !== 'page') { close(); } });

    // ---- home: help for this screen ----
    function loadHome() {
        get(BASE + 'help/page?path=' + encodeURIComponent(PATH)).then(function (d) {
            home = d;
            panel.querySelector('#avh-sub').textContent = d.user && d.user.group ? 'Signed in as ' + d.user.group : '';
            renderFoot();
            showHome();
        }).catch(failed);
    }
    function topicButtons(list) {
        return '<ul class="avh-list">' + list.map(function (t) {
            return '<li><button type="button" class="avh-link" data-topic="' + esc(t.id) + '">' + esc(t.title) + '</button></li>';
        }).join('') + '</ul>';
    }
    function showHome() {
        var d = home; if (!d) { return; }
        var h = '';
        h += '<div class="avh-examples"><div class="avh-label">Try asking</div>' + d.examples.map(function (q) {
            return '<button type="button" class="avh-chip" data-q="' + esc(q) + '">' + esc(q) + '</button>';
        }).join('') + '</div>';
        if (MODE === 'page') {
            h += d.sections.map(function (s) {
                return '<div class="avh-label">' + esc(s.title) + '</div>' + topicButtons(s.topics);
            }).join('');
            body.innerHTML = h; body.scrollTop = 0;
            return;
        }
        h += '<div class="avh-label">Help for this screen' + (d.section ? ' · ' + esc(d.section.title) : '') + '</div>';
        if (d.page_topics && d.page_topics.length) {
            if (d.section && d.section.intro_html) { h += '<p class="avh-intro">' + d.section.intro_html + '</p>'; }
            h += topicButtons(d.page_topics);
        } else {
            h += '<p class="avh-muted">There are no checked instructions for this screen yet. The manual covers <b>Hotel Rates</b> so far; other sections are being added. You can still ask a question above or open the printable manual.</p>';
        }
        h += '<details class="avh-all"><summary>All topics</summary>' + d.sections.map(function (s) {
            return '<div class="avh-sec">' + esc(s.title) + '</div>' + topicButtons(s.topics);
        }).join('') + '</details>';
        body.innerHTML = h;
        body.scrollTop = 0;
    }
    function renderFoot() {
        var d = home; if (!d) { return; }
        var f = '<a href="' + esc(d.pdf.view) + '" target="_blank" rel="noopener">View PDF</a> · <a href="' + esc(d.pdf.download) + '">Download PDF</a>';
        f += '<div class="avh-muted">Checked against the system on ' + esc(niceDate(d.checked)) + '. Help only shows the way — it never changes anything.</div>';
        if (d.check) { f += '<div class="avh-muted">Manual check: ' + d.check.ok + ' of ' + d.check.total + ' topics match (' + esc(d.check.at) + ') · <a href="' + esc(d.check.url) + '" target="_blank" rel="noopener">run the check</a></div>'; }
        else if (d.user && d.user.admin) { f += '<div class="avh-muted"><a href="' + esc(BASE + 'help/check') + '" target="_blank" rel="noopener">Check the manual against the system</a></div>'; }
        panel.querySelector('#avh-foot').innerHTML = f;
    }

    // ---- asking ----
    function ask(q) {
        q = (q || '').trim(); if (!q) { input.focus(); return; }
        input.value = q;
        body.innerHTML = '<div class="avh-muted">Looking it up…</div>';
        get(BASE + 'help/ask?q=' + encodeURIComponent(q) + '&path=' + encodeURIComponent(PATH)).then(function (d) { renderResult(d, q); }).catch(failed);
    }
    function showTopic(id) {
        body.innerHTML = '<div class="avh-muted">Opening…</div>';
        get(BASE + 'help/topic/' + encodeURIComponent(id) + '?path=' + encodeURIComponent(PATH)).then(function (d) { renderResult(d, null); }).catch(failed);
    }
    panel.querySelector('.avh-ask').addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
    body.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('[data-topic],[data-q],[data-home]') : null;
        if (!t) { return; }
        if (t.hasAttribute('data-topic')) { showTopic(t.getAttribute('data-topic')); }
        else if (t.hasAttribute('data-q')) { ask(t.getAttribute('data-q')); }
        else if (t.hasAttribute('data-home')) { input.value = ''; showHome(); input.focus(); }
    });

    function backBar(q) {
        return '<div class="avh-back"><button type="button" class="avh-link" data-home="1">← Help for this screen</button>' + (q ? '<span class="avh-muted">You asked: “' + esc(q) + '”</span>' : '') + '</div>';
    }
    function renderResult(d, q) {
        var h = backBar(q);
        if (d.result === 'answer' && d.answer) {
            if (d.also) { h += '<div class="avh-label">Your question has two parts — 1 of 2</div>'; }
            h += answerCard(d.answer);
            if (d.also) { h += '<div class="avh-label">2 of 2</div>' + answerCard(d.also); }
            if (d.others && d.others.length) { h += '<div class="avh-label">Did you mean something else?</div>' + topicButtons(d.others); }
        } else if (d.result === 'unsure') {
            h += '<div class="avh-card"><p>' + esc(d.message) + '</p>' + topicButtons(d.options || []) + '</div>';
        } else {
            h += '<div class="avh-card avh-warn"><p><b>No checked answer.</b> ' + esc(d.message || '') + '</p>' +
                '<p><a href="' + esc(d.pdf.view) + '" target="_blank" rel="noopener">Open the printable manual</a></p></div>';
            if (home) { h += '<div class="avh-label">Questions you can ask</div>' + home.examples.map(function (x) { return '<button type="button" class="avh-chip" data-q="' + esc(x) + '">' + esc(x) + '</button>'; }).join(''); }
        }
        body.innerHTML = h; body.scrollTop = 0;
    }
    function answerCard(a) {
        var h = '<article class="avh-card avh-answer"><div class="avh-sec">' + esc(a.section) + '</div><h3>' + esc(a.title) + '</h3>';
        if (!a.access.ok) {
            h += '<div class="avh-access avh-no">' + ICON_LOCK + ' <span>' + a.access.note_html + '</span></div>';
        } else if (a.access.needs && a.access.needs.length) {
            h += '<div class="avh-access avh-yes">You have access: ' + a.access.needs.map(function (n) { return esc(n.name); }).join(', ') + '</div>';
        }
        h += '<p class="avh-summary">' + a.summary_html + '</p>';
        if (a.steps.length) {
            h += '<ol class="avh-steps">' + a.steps.map(function (s) {
                var li = '<li><div>' + s.html + '</div>';
                if (s.locked) { li += '<div class="avh-lock">' + ICON_LOCK + ' Needs <b>' + esc(s.locked.name) + '</b> — ask: ' + esc(s.locked.holders.join(', ')) + '</div>'; }
                if (s.link) {
                    li += s.link.allowed
                        ? '<a class="avh-go" href="' + esc(s.link.href) + '">' + esc(s.link.label) + ' →</a>'
                        : '<span class="avh-go avh-off" title="' + esc(s.link.title || '') + '">' + ICON_LOCK + ' ' + esc(s.link.label) + '</span><div class="avh-lock">' + esc(s.link.title || '') + '</div>';
                }
                return li + '</li>';
            }).join('') + '</ol>';
        }
        if (a.notes.length) { h += '<div class="avh-notes">' + a.notes.map(function (n) { return '<p>' + n + '</p>'; }).join('') + '</div>'; }
        a.images.forEach(function (im) {
            h += '<figure class="avh-fig"><a href="' + esc(im.src) + '" target="_blank" rel="noopener" title="Open the picture full size"><img src="' + esc(im.src) + '" alt="' + esc(im.caption) + '" loading="lazy"></a><figcaption>' + esc(im.caption) + '</figcaption></figure>';
        });
        if (a.related.length) { h += '<div class="avh-label">Related</div>' + topicButtons(a.related); }
        h += '<div class="avh-meta">Checked against the system on ' + esc(niceDate(a.checked)) + (a.manual_ref ? ' · Printable manual: ' + esc(a.manual_ref) : '') + '</div>';
        return h + '</article>';
    }

    if (MODE === 'page') { loadHome(); }
}());
