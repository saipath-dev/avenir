<?php /** tf-mailfix-r2 — My Profile → Mailbox: the signed-in person's own sending mailbox (MailboxController). */ ?>
<div class="modal fade" id="mailboxModal" tabindex="-1" aria-labelledby="mailboxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="mailbox_form" autocomplete="off" novalidate>
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="mailboxModalLabel">Mailbox — e-mail sending</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Quotations and hotel letters are e-mailed from this mailbox. Type the <b>mailbox's own password</b> (the one set at the mail provider, Lolipop) — not your Avenir login password. It is checked with the mail server first and saved only if the server accepts it. Nothing is sent.</p>
                    <div class="mb-3">
                        <label for="mb_email" class="form-label">Mailbox address</label>
                        <input type="email" class="form-control" id="mb_email" autocomplete="off" spellcheck="false">
                    </div>
                    <div class="small mb-1">Mail server: <span id="mb_server" class="fw-semibold">…</span></div>
                    <div class="small mb-3">Password saved: <span id="mb_has" class="fw-semibold">…</span></div>
                    <div class="mb-3">
                        <label for="mb_password" class="form-label">Mailbox password</label>
                        <input type="password" class="form-control" id="mb_password" autocomplete="new-password">
                    </div>
                    <div id="mb_result" class="small" role="status" aria-live="polite"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" id="mb_check">Check saved login</button>
                    <button type="submit" class="btn btn-primary" id="mb_save">Check and save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    (function () {
        var BASE = <?= json_encode(base_url('my_profile/mailbox')) ?>;
        var modal = document.getElementById('mailboxModal');
        if (!modal) { return; }
        var email = document.getElementById('mb_email'), pw = document.getElementById('mb_password'), out = document.getElementById('mb_result');
        var btnCheck = document.getElementById('mb_check'), btnSave = document.getElementById('mb_save');
        function show(ok, msg) {
            out.className = 'small alert ' + (ok ? 'alert-success' : 'alert-danger') + ' mb-0';
            out.textContent = msg;
        }
        function busy(on, text) {
            btnCheck.disabled = on; btnSave.disabled = on;
            if (on) { out.className = 'small text-muted'; out.textContent = text; }
        }
        function call(method, path, body) {
            return fetch(BASE + path, { method: method, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: body ? JSON.stringify(body) : undefined })
                .then(function (r) { return r.json(); });
        }
        function load() {
            out.className = 'small'; out.textContent = '';
            call('GET', '').then(function (d) {
                if (d.status !== 200) { show(false, d.message || 'The mailbox could not be read.'); return; }
                email.value = d.data.email || '';
                document.getElementById('mb_server').textContent = d.data.server;
                document.getElementById('mb_has').textContent = d.data.has_password ? 'yes' : 'no';
            }).catch(function () { show(false, 'The mailbox could not be read just now.'); });
        }
        modal.addEventListener('show.bs.modal', load);
        modal.addEventListener('hidden.bs.modal', function () { pw.value = ''; });
        btnCheck.addEventListener('click', function () {
            busy(true, 'Checking the saved login with the mail server… (nothing is sent)');
            call('POST', '/check').then(function (d) { busy(false); show(d.status === 200, d.message); })
                .catch(function () { busy(false); show(false, 'The check could not be made just now.'); });
        });
        document.getElementById('mailbox_form').addEventListener('submit', function (e) {
            e.preventDefault();
            var p = pw.value;
            pw.value = '';   // never kept in the page
            if (!p) { show(false, 'Type the mailbox password.'); return; }
            busy(true, 'Checking the login with the mail server… (nothing is sent)');
            call('POST', '/save', { email: email.value.trim(), password: p }).then(function (d) {
                busy(false); show(d.status === 200, d.message);
                if (d.status === 200) { document.getElementById('mb_has').textContent = 'yes'; }
            }).catch(function () { busy(false); show(false, 'The check could not be made just now. Nothing was saved.'); });
        });
    }());
</script>
