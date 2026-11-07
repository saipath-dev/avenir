<script>
/*
 * tf-eye-r1 (7 Nov 2026, Shantha) — a show / hide (eye) toggle for password fields.
 *  - wires the existing eye button on the login page (.password-addon inside .auth-pass-inputgroup);
 *  - adds an eye button to any <input data-eye> (the account forms).
 * The field shows its text only while the eye is pressed on; it is hidden again when its form is submitted or reset.
 */
(function () {
    'use strict';
    function setIcon(btn, shown) {
        var i = btn.querySelector('i');
        if (i) {
            if (/\bri-/.test(i.className)) {
                i.className = i.className.replace(/\bri-eye(-off)?-fill\b/, shown ? 'ri-eye-off-fill' : 'ri-eye-fill');
            } else {
                i.className = i.className.replace(/\bbi-eye(-slash)?\b/, shown ? 'bi-eye-slash' : 'bi-eye');
            }
        }
        btn.setAttribute('aria-label', shown ? 'Hide password' : 'Show password');
        btn.setAttribute('aria-pressed', shown ? 'true' : 'false');
        btn.title = shown ? 'Hide password' : 'Show password';
    }
    function wire(input, btn) {
        if (!input || !btn || btn.dataset.eyeWired) return;
        btn.dataset.eyeWired = '1';
        setIcon(btn, false);
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            setIcon(btn, show);
            input.focus();
        });
        var form = input.form;
        if (form) {
            var hideAgain = function () { input.type = 'password'; setIcon(btn, false); };
            form.addEventListener('submit', hideAgain);
            form.addEventListener('reset', hideAgain);
        }
    }
    function init() {
        // 1. the login page's existing eye button
        document.querySelectorAll('.auth-pass-inputgroup').forEach(function (g) {
            wire(g.querySelector('.password-input, input[type="password"]'), g.querySelector('.password-addon'));
        });
        // 2. fields marked data-eye: wrap them and add the button
        document.querySelectorAll('input[data-eye]').forEach(function (input) {
            if (input.dataset.eyeDone) return;
            input.dataset.eyeDone = '1';
            var wrap = document.createElement('div');
            wrap.className = 'position-relative';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            input.classList.add('pe-5');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted';
            btn.innerHTML = '<i class="bi bi-eye align-middle"></i>';
            wrap.appendChild(btn);
            wire(input, btn);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
</script>
