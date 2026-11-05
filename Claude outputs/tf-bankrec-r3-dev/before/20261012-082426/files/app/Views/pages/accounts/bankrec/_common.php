<?php
/** tf-bankrec-r1 — shared styles and helpers of the Daily Bank Reconciliation pages. */
?>
<style>
    .brc { --p:#842484; --p-soft:#f3e7f3; --ink:#24303d; --muted:#667085; --line:#e4e8ee; --bg:#f7f8fb; color:var(--ink); }
    .brc .btn-p { background:var(--p); border-color:var(--p); color:#fff; } .brc .btn-p:hover { background:#6f1d6f; color:#fff; }
    .brc .btn-op { border-color:var(--p); color:var(--p); } .brc .btn-op:hover { background:var(--p-soft); color:var(--p); }
    .brc .card { border:1px solid var(--line); border-radius:12px; box-shadow:none; }
    .brc .card-h { font-weight:700; font-size:14px; padding:12px 16px; border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
    .brc .muted { color:var(--muted); } .brc .small2 { font-size:12px; }
    .brc .mono, .brc .num { font-family: ui-monospace, Menlo, Consolas, monospace; font-variant-numeric: tabular-nums; }
    .brc .num { text-align:right; white-space:nowrap; }
    .brc .pill { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:2px 10px; font-size:12px; font-weight:600; white-space:nowrap; }
    .brc .pill::before { content:''; width:7px; height:7px; border-radius:50%; background:currentColor; opacity:.8; }
    .brc .s-not_set_up, .brc .s-none, .brc .s-void, .brc .s-superseded, .brc .s-withdrawn, .brc .s-undone, .brc .s-cleared { background:#eef0f3; color:#5b6472; }
    .brc .s-insufficient, .brc .s-proposed, .brc .s-draft, .brc .s-prepared { background:#fff4e0; color:#8a5300; }
    .brc .s-open_items, .brc .s-changed { background:#fdeee4; color:#9a3d06; }
    .brc .s-explained, .brc .s-accepted, .brc .s-approved, .brc .s-timing { background:#e8f0fe; color:#1d4ed8; }
    .brc .s-reviewed, .brc .s-confirmed, .brc .s-matched, .brc .s-active { background:#e4f4ea; color:#1f7a43; }
    .brc .s-rejected, .brc .s-returned, .brc .s-blocked { background:#fde8e8; color:#b42318; }
    .brc .s-duplicate { background:#f4ebff; color:#6b21a8; } .brc .s-excluded { background:#eef0f3; color:#344054; }
    .brc table.t { width:100%; font-size:13px; } .brc table.t th { font-size:11.5px; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); font-weight:600; background:#fafbfc; position:sticky; top:0; z-index:1; }
    .brc table.t td, .brc table.t th { padding:6px 8px; border-bottom:1px solid #eef1f5; vertical-align:top; }
    .brc table.t tr.sel td { background:#f6effa; }
    .brc .scroll { max-height:460px; overflow:auto; }
    .brc .kv { display:grid; grid-template-columns: 1fr auto; gap:4px 16px; font-size:13.5px; } .brc .kv .k { color:var(--muted); } .brc .kv .v { text-align:right; font-variant-numeric: tabular-nums; }
    .brc .kv .total { font-weight:700; border-top:1px solid var(--line); padding-top:4px; }
    .brc .nav-tabs .nav-link { color:var(--muted); font-weight:600; font-size:13.5px; } .brc .nav-tabs .nav-link.active { color:var(--p); border-bottom:2px solid var(--p); }
    .brc .step { display:flex; gap:10px; align-items:flex-start; font-size:13px; } .brc .step strong { color:var(--p); } .brc .note { background:#f8f5fb; border:1px solid #eadcf0; border-radius:10px; padding:8px 12px; font-size:13px; color:var(--ink); }
    .brc .step > b { display:inline-flex; width:22px; height:22px; border-radius:50%; background:var(--p-soft); color:var(--p); align-items:center; justify-content:center; font-size:12px; flex:0 0 22px; }
    .brc .err { color:#b42318; } .brc .warn { color:#8a5300; }
    .brc .bar { background:#fff; border:1px solid var(--line); border-radius:12px; padding:8px 12px; display:flex; flex-direction:row; gap:12px; align-items:center; flex-wrap:wrap; }
    .brc .kv .k.sub { padding-left:18px; } .brc .s-charge { background:#e6f6f4; color:#0f766e; }
    .brc .testbar-note { font-size:12px; }
    @media (max-width: 768px) { .brc .scroll { max-height:none; } .brc .hide-sm { display:none; } }
</style>
<script>
window.BRC = (function () {
    const base = <?= json_encode(rtrim(base_url('accounts/daily_bank_rec'), '/')) ?>;
    const csrf = { name: <?= json_encode(csrf_token()) ?>, hash: <?= json_encode(csrf_hash()) ?>, header: <?= json_encode(csrf_header()) ?> };
    const access = <?= json_encode($brc ?? []) ?>;
    const H = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
    async function get(path, params) {
        const q = params ? '?' + new URLSearchParams(params).toString() : '';
        try { const r = await fetch(base + '/' + path + q, { headers: H, credentials: 'same-origin' }); return await r.json(); }
        catch (e) { return { status: 0, message: 'Network error' }; }
    }
    async function post(path, data) {
        const fd = data instanceof FormData ? data : new FormData();
        if (!(data instanceof FormData)) { for (const [k, v] of Object.entries(data || {})) { fd.append(k, (v !== null && typeof v === 'object') ? JSON.stringify(v) : (v ?? '')); } }
        fd.set(csrf.name, csrf.hash);
        try {
            const r = await fetch(base + '/' + path, { method: 'POST', body: fd, headers: H, credentials: 'same-origin' });
            const j = await r.json();
            if (j && j.csrf) csrf.hash = j.csrf;
            return j;
        } catch (e) { return { status: 0, message: 'Network error' }; }
    }
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    function money(v, cur) {
        if (v === null || v === undefined || v === '') return '<span class="muted">—</span>';
        const n = Number(v); const s = Math.abs(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return (n < 0 ? '−' : '') + s + (cur ? ' <span class="muted small2">' + esc(cur) + '</span>' : '');
    }
    const pill = (code, label) => '<span class="pill s-' + esc(code) + '">' + esc(label ?? code) + '</span>';
    function done(j, reload) {
        if (j.status === 200) { Swal.fire({ icon: 'success', title: 'Done', text: j.message, timer: 2200, showConfirmButton: false }).then(() => reload && reload()); }
        else { Swal.fire({ icon: j.status === 409 || j.status === 400 ? 'warning' : 'error', title: 'Not done', text: j.message || 'Something went wrong.' }); }
        return j;
    }
    /** a small form in a dialog; fields: [{name, label, type, value, options, min}] → values or null */
    async function ask(title, fields, confirm) {
        const html = fields.map(f => {
            const id = 'brcf_' + f.name;
            let input;
            if (f.type === 'select') input = '<select class="form-select" id="' + id + '">' + f.options.map(o => '<option value="' + esc(o[0]) + '"' + (String(o[0]) === String(f.value ?? '') ? ' selected' : '') + '>' + esc(o[1]) + '</option>').join('') + '</select>';
            else if (f.type === 'textarea') input = '<textarea class="form-control" rows="2" id="' + id + '">' + esc(f.value ?? '') + '</textarea>';
            else input = '<input class="form-control" id="' + id + '" type="' + (f.type || 'text') + '" value="' + esc(f.value ?? '') + '">';
            return '<div class="text-start mb-2"><label class="form-label small fw-semibold mb-1" for="' + id + '">' + esc(f.label) + '</label>' + input + (f.help ? '<div class="small text-muted">' + esc(f.help) + '</div>' : '') + '</div>';
        }).join('');
        const r = await Swal.fire({ title, html, showCancelButton: true, confirmButtonText: confirm || 'Save', confirmButtonColor: '#842484', focusConfirm: false,
            preConfirm: () => {
                const out = {};
                for (const f of fields) {
                    const v = document.getElementById('brcf_' + f.name).value.trim();
                    if (f.min && v.length < f.min) { Swal.showValidationMessage(f.label + ': at least ' + f.min + ' characters'); return false; }
                    if (f.required && !v) { Swal.showValidationMessage(f.label + ' is required'); return false; }
                    out[f.name] = v;
                }
                return out;
            } });
        return r.isConfirmed ? r.value : null;
    }
    const today = () => new Date().toISOString().slice(0, 10);
    return { base, csrf, access, get, post, esc, money, pill, done, ask, today };
})();
</script>
