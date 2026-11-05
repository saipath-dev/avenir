<?php
/**
 * tf-stage1 (29 Oct 2026) — the Master panel look shared by the TourFlow masters (the Place to Visit master's pattern):
 * orchid purple headers, Avenir green actions, one prominent search box, the long explanation folded behind "How this
 * works", and the editor's secondary sections under "More details". Included at the end of a master page's <style>;
 * the page keeps its own markup and behaviour.
 */
?>
<style>
    .tf.tf-skin { --tf-navy:#842484; --tf-navy-2:#6d1d6d; --tf-teal:#5f9a36; --tf-teal-soft:#eef7e6; --tf-green:#5f9a36; --tf-green-soft:#eef7e6; --tf-blue-soft:#f3e6f3; --tf-line:#e2e6ec; }
    .tf.tf-skin .tf-title h4 { color:#842484; font-weight:700; }
    .tf.tf-skin .tf-card-h h6 { color:#842484; font-weight:700; }
    .tf.tf-skin .btn-primary { background:#78b848; border-color:#78b848; color:#fff; }
    .tf.tf-skin .btn-primary:hover, .tf.tf-skin .btn-primary:focus { background:#5f9a36; border-color:#5f9a36; color:#fff; }
    .tf.tf-skin .tf-title > div:last-child .btn-primary { height:44px; border-radius:22px; padding:0 22px; font-weight:600; }
    .tf.tf-skin .pill-blue { background:#f3e6f3; color:#842484; }
    .tf.tf-skin .filters #l_q { flex:1 1 320px; max-width:none; height:44px; border-radius:22px; padding-left:40px; font-size:14.5px; border:1.5px solid #d9c3d9;
        background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23842484' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/%3E%3C/svg%3E") no-repeat 14px center; }
    .tf.tf-skin .filters #l_q:focus { border-color:#842484; box-shadow:0 0 0 3px rgba(132,36,132,.12); }
    .tf.tf-skin .filters { width:100%; }
    .tf.tf-skin .tf-card-h { flex-direction:column; align-items:stretch; }
    .tf.tf-skin .tf-card-h > h6 { margin-bottom:6px; }
    .tf.tf-skin details.tf-how { margin-top:4px; }
    .tf.tf-skin details.tf-how summary { cursor:pointer; color:#842484; font-size:12.5px; font-weight:600; list-style:none; }
    .tf.tf-skin details.tf-how summary::before { content:"▸ "; }
    .tf.tf-skin details.tf-how[open] summary::before { content:"▾ "; }
    .tf.tf-skin details.tf-how .sub { margin-top:6px; }
    .tf.tf-skin .tbl th { background:#fafbfc; }
    .tf.tf-skin .tbl tbody tr:hover td { background:#fcf8fc; }
    .modal.tf.tf-skin .modal-header { background:#842484; color:#fff; }
    .modal.tf.tf-skin .modal-header .btn-close { filter:invert(1); }
    .modal.tf.tf-skin .modal-header .modal-title { color:#fff; }
    .modal.tf.tf-skin .btn-primary { background:#78b848; border-color:#78b848; }
    .modal.tf.tf-skin .btn-primary:hover { background:#5f9a36; border-color:#5f9a36; }
    .modal.tf.tf-skin .sec { color:#842484; }
    .modal.tf.tf-skin details.tf-more { border:1px dashed #d9c3d9; border-radius:10px; padding:8px 12px; margin-top:12px; }
    .modal.tf.tf-skin details.tf-more > summary { cursor:pointer; font-weight:600; color:#5f6f80; font-size:12.5px; list-style:none; }
    .modal.tf.tf-skin details.tf-more > summary::before { content:"▸ "; color:#842484; }
    .modal.tf.tf-skin details.tf-more[open] > summary::before { content:"▾ "; }
    .modal.tf.tf-skin details.tf-more > summary small { font-weight:400; }
</style>
<script>
    // the long explanation under the title folds behind "How this works"; the search box leads the filter row
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.querySelector('.tf.tf-skin');
        if (!root) return;
        var sub = root.querySelector('.tf-title .sub');
        if (sub && sub.textContent.trim().length > 160) {
            var d = document.createElement('details'); d.className = 'tf-how';
            var s = document.createElement('summary'); s.textContent = 'How this works';
            sub.parentNode.insertBefore(d, sub); d.appendChild(s); d.appendChild(sub);
        }
        var q = root.querySelector('.filters #l_q');
        if (q && q.parentNode.firstElementChild !== q) q.parentNode.insertBefore(q, q.parentNode.firstElementChild);
    });
</script>
