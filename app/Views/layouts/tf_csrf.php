<?php
/*
 * tf-e8-access-r1 — included in <head> by layouts/head.php and by the three print pages that send receipts.
 *  1. The security token for this session, as two meta tags (the token cookie is HttpOnly, so scripts read it here).
 *  2. Every same-site request that changes something (any method except GET / HEAD), whether made by jQuery, by
 *     XMLHttpRequest or by fetch(), carries the token in the X-CSRF-TOKEN header — unless the page already set it.
 *     A newer token in a response header replaces the one in the page.
 *  3. tfConfirmReason({...}): the confirmation dialog with a required reason (deletes and corrections, at least 5 characters).
 * No visual change.
 */
?>
    <meta name="tf-csrf-name" content="<?= esc(csrf_token(), 'attr') ?>">
    <meta name="tf-csrf-token" content="<?= esc(csrf_hash(), 'attr') ?>">
    <script>
    (function () {
        if (window.tfCsrf) { return; }
        var meta = function () { return document.querySelector('meta[name="tf-csrf-token"]'); };
        var tfCsrf = window.tfCsrf = {
            token: function () { var m = meta(); return m ? (m.getAttribute('content') || '') : ''; },
            set: function (t) { var m = meta(); if (m && t && /^[0-9a-f]{32}$/.test(t)) { m.setAttribute('content', t); } },
            same: function (url) { try { return new URL(String(url), window.location.href).origin === window.location.origin; } catch (e) { return false; } },
            changes: function (method) { method = String(method || 'GET').toUpperCase(); return method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS'; }
        };
        var X = XMLHttpRequest.prototype, open = X.open, send = X.send, setHeader = X.setRequestHeader;
        X.open = function (method, url) { this.__tfMethod = method; this.__tfUrl = url; this.__tfHasToken = false; return open.apply(this, arguments); };
        X.setRequestHeader = function (name, value) { if (String(name).toLowerCase() === 'x-csrf-token') { this.__tfHasToken = true; } return setHeader.apply(this, arguments); };
        X.send = function () {
            var x = this;
            try {
                if (!x.__tfHasToken && tfCsrf.changes(x.__tfMethod) && tfCsrf.same(x.__tfUrl) && tfCsrf.token()) { setHeader.call(x, 'X-CSRF-TOKEN', tfCsrf.token()); }
                x.addEventListener('load', function () { try { if (tfCsrf.same(x.__tfUrl)) { tfCsrf.set(x.getResponseHeader('X-CSRF-TOKEN')); } } catch (e) {} }); // tf-e8-access-r3 (F2): our own site only
            } catch (e) {}
            return send.apply(this, arguments);
        };
        if (window.fetch) {
            var f = window.fetch;
            window.fetch = function (input, init) {
                try {
                    var url = (typeof input === 'string' || input instanceof URL) ? String(input) : ((input && input.url) || '');
                    var method = (init && init.method) || (input && typeof input === 'object' && input.method) || 'GET';
                    if (tfCsrf.changes(method) && tfCsrf.same(url) && tfCsrf.token()) {
                        init = Object.assign({}, init || {});
                        var h = new Headers(init.headers || ((input && typeof input === 'object' && input.headers) || {}));
                        if (!h.has('X-CSRF-TOKEN')) { h.set('X-CSRF-TOKEN', tfCsrf.token()); }
                        init.headers = h;
                    }
                } catch (e) {}
                return f.call(this, input, init).then(function (r) { try { if (tfCsrf.same(url)) { tfCsrf.set(r.headers.get('X-CSRF-TOKEN')); } } catch (e) {} return r; }); // tf-e8-access-r3 (F2)
            };
        }
        window.tfConfirmReason = function (opts) {
            var o = Object.assign({ icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, go ahead' }, opts || {}, {
                input: 'text',
                inputLabel: 'Reason (required)',
                inputPlaceholder: 'Why is this needed? (at least 5 characters)',
                inputAttributes: { maxlength: 300, autocomplete: 'off' },
                inputValidator: function (v) { if (!v || String(v).trim().length < 5) { return 'Please give the reason (at least 5 characters).'; } }
            });
            return Swal.fire(o);
        };
        window.tfReasonFrom = function (selector) {
            var el = document.querySelector(selector); var v = el ? String(el.value || '').trim() : '';
            if (v.length < 5) { Swal.fire({ icon: 'warning', title: 'Reason required', text: 'Please give the reason (at least 5 characters).' }); return null; }
            return v;
        };
    })();
    </script>
