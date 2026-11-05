<?php
/**
 * tf-confirmed-r3 (3 Nov 2026) — the red "Changed after the final confirmation" alert in the top bar. Hidden until
 * the server says there is something, and the server answers only a sign-in holding Inquiries. A tour the agent has
 * already confirmed to the client, where a desk has since reopened its work: the tour, the department, who, when,
 * why, and the link to where the work now is. It reads only; the work is finished on its own desk and the updated
 * confirmation is sent from Confirmed Tours.
 */
?>
<div class="dropdown topbar-head-dropdown ms-1 header-item d-none" id="reopenAlertDropdown">
    <button type="button" class="btn btn-sm d-flex align-items-center gap-1 rounded-pill" id="page-header-reopenalert" data-bs-toggle="dropdown"
            data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false"
            style="background:#8a1c30;color:#fff;font-weight:600;padding:5px 12px;white-space:nowrap">
        <i class="bx bx-error fs-16"></i> <span id="reopen_alert_count">0</span> <span class="d-none d-md-inline">changed after confirmation</span>
    </button>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-reopenalert" style="min-width:420px">
        <div class="p-3 rounded-top" style="background:#8a1c30;color:#fff">
            <h6 class="m-0 fs-15 fw-semibold text-white">Changed after the final confirmation</h6>
            <div style="font-size:12px;opacity:.92">The client was already told these tours were confirmed, and a department has since reopened its work. Finish the work, then send the updated confirmation from Confirmed Tours.</div>
        </div>
        <div style="max-height:340px;overflow-y:auto" class="px-3 py-2" id="reopen_alert_list"></div>
        <div class="text-center pt-2 pb-3"><a href="<?= base_url('tour/intake/confirmed') ?>" class="btn btn-sm" style="background:#8a1c30;color:#fff">Open Confirmed Tours <i class="ri-arrow-right-line ms-1"></i></a></div>
    </div>
</div>
<script>
(function () {
    'use strict';
    const URL_ = '<?= base_url('notification/reopened_tours') ?>';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    async function check() {
        const box = document.getElementById('reopenAlertDropdown');
        if (!box) return;
        try {
            const r = await fetch(URL_, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!r.ok) return;
            const j = await r.json();
            const d = (j && j.data) || {};
            if (!d.allowed || !d.count) { box.classList.add('d-none'); return; }
            document.getElementById('reopen_alert_count').textContent = d.count;
            document.getElementById('reopen_alert_list').innerHTML = (d.items || []).map(m =>
                `<a class="d-block text-reset py-2" style="border-bottom:1px dashed #e6e9ee;text-decoration:none" href="${esc(m.work_url)}">`
                + `<b>${esc(m.tour_no)}</b>${m.inquiry_no ? ' · ' + esc(m.inquiry_no) : ''}${m.mine ? ' <span style="background:#8a1c30;color:#fff;border-radius:10px;padding:1px 7px;font-size:10.5px">yours</span>' : ''}`
                + `<div style="font-size:12px">${esc(m.agent_name || '')}</div>`
                + `<div style="font-size:12px;color:#8a1c30"><b>${esc(m.what)}</b> reopened by ${esc(m.by || 'a desk')} on ${esc(String(m.at || '').slice(0, 16))}</div>`
                + (m.why ? `<div style="font-size:11.5px;color:#5f6f80">${esc(m.why)}</div>` : '')
                + `<div style="font-size:11px;color:#6b7a8c">the client was told on ${esc(String(m.told_at || '').slice(0, 16))}${m.handler_name ? ' · handling agent ' + esc(m.handler_name) : ''}</div></a>`
            ).join('') + (d.count > (d.items || []).length ? `<div class="py-2" style="font-size:12px;color:#6b7a8c">and ${d.count - d.items.length} more…</div>` : '');
            box.classList.remove('d-none');
        } catch (e) { /* the alert is a help, never in the way */ }
    }
    window.AvhReopenAlert = check;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', check); else check();
    setInterval(check, 120000);
})();
</script>
