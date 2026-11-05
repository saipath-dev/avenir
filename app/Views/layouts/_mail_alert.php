<?php
/**
 * tf-tourmail-r1 (10 Oct 2026) — the red "Mails not sent" alert in the top bar, on every screen. Hidden until the
 * server says there is something (and only for a sign-in holding Hotel Bookings: notification/unsent_mails answers nobody else).
 * It reads only; the mail is sent from the stay (Hotel Desk) or the line (Activities desk), or recorded as sent another way.
 */
?>
<div class="dropdown topbar-head-dropdown ms-1 header-item d-none" id="mailAlertDropdown">
    <button type="button" class="btn btn-sm d-flex align-items-center gap-1 rounded-pill" id="page-header-mailalert" data-bs-toggle="dropdown"
            data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false"
            style="background:#b23a32;color:#fff;font-weight:600;padding:5px 12px;white-space:nowrap">
        <i class="bx bx-envelope fs-16"></i> <span id="mail_alert_count">0</span> <span class="d-none d-md-inline">mail(s) not sent</span>
    </button>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-mailalert" style="min-width:380px">
        <div class="p-3 rounded-top" style="background:#b23a32;color:#fff">
            <h6 class="m-0 fs-15 fw-semibold text-white">Mails not sent</h6>
            <div style="font-size:12px;opacity:.9">Hotel, restaurant and transport requests that did not go. Send each from its stay (Hotel Desk) or line (Activities), or record it as sent another way.</div>
        </div>
        <div style="max-height:320px;overflow-y:auto" class="px-3 py-2" id="mail_alert_list"></div>
        <div class="text-center pt-2 pb-3"><a href="<?= base_url('tour/hotel_bookings/mails?state=not_sent') ?>" class="btn btn-sm" style="background:#b23a32;color:#fff">Open Sent Mails <i class="ri-arrow-right-line ms-1"></i></a></div>
    </div>
</div>
<script>
(function () {
    'use strict';
    const URL_ = '<?= base_url('notification/unsent_mails') ?>';
    const HOTELS = '<?= base_url('tour/hotel_bookings') ?>';
    const TRANSPORT = '<?= base_url('tour/transport_ops') ?>';   // tf-transmail-r1: a transport request opens on its own desk
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    async function check() {
        const box = document.getElementById('mailAlertDropdown');
        if (!box) return;
        try {
            const r = await fetch(URL_, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!r.ok) return;
            const j = await r.json();
            const d = (j && j.data) || {};
            if (!d.allowed || !d.count) { box.classList.add('d-none'); return; }
            document.getElementById('mail_alert_count').textContent = d.count;
            document.getElementById('mail_alert_list').innerHTML = (d.items || []).map(m => `<a class="d-block text-reset py-2" style="border-bottom:1px dashed #e6e9ee;text-decoration:none" href="${m.type === 'transport' ? TRANSPORT : HOTELS}?tour=${m.tour_id}"><b>${esc(m.tour_no)}</b> · ${esc(m.what)} — <b>${esc(m.name || '')}</b><div style="font-size:11.5px;color:#b23a32">${esc(m.why || 'Not sent.')}</div><div style="font-size:11px;color:#6b7a8c">${esc(String(m.at || '').slice(0, 16))}${m.to ? ' · ' + esc(m.to) : ''}</div></a>`).join('') + (d.count > (d.items || []).length ? `<div class="py-2" style="font-size:12px;color:#6b7a8c">and ${d.count - d.items.length} more…</div>` : '');
            box.classList.remove('d-none');
        } catch (e) { /* the alert is a help, never in the way */ }
    }
    window.AvhMailAlert = check;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', check); else check();
    setInterval(check, 120000);
})();
</script>
