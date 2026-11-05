<?= view('layouts/head') ?>
<style>
    /* tf-tourmail-r1 — Sent Mails: every hotel and restaurant e-mail of the tours, sent or not sent */
    .tf { --tf-navy:#1e3553; --tf-red:#b23a32; --tf-green:#1d6f47; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-ink:#1d2733; color:var(--tf-ink); }
    .tf .tf-card { background:#fff; border:1px solid var(--tf-line); border-radius:10px; box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-b { padding:10px 14px; }
    .tf .chip { border:1px solid var(--tf-line); background:#fff; border-radius:9px; padding:5px 11px; font-size:12px; font-weight:500; cursor:pointer; color:var(--tf-ink); }
    .tf .chip.on { background:#e6eef8; border-color:transparent; color:var(--tf-navy); font-weight:600; }
    .tf .chip.red { color:var(--tf-red); }
    .tf .chip.red.on { background:#fbe9e7; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf h4 { color:var(--tf-navy); font-weight:600; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">
                <?= view('layouts/tourdesk_bar', ['tourdesk_desk_only' => 'reservation']) /* tf-resdesk-r1: the Hotel Reservation Desk strip */ ?>
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="mb-1">Sent Mails</h4>
                        <div class="help" style="max-width:860px">Every booking request and voucher e-mailed to a hotel, and every booking voucher e-mailed to a restaurant — who sent it, when, to which address, and the copy as it went. A mail that did <b>not</b> go is in red with the reason, and stays on the red “Mails not sent” alert at the top of every screen until it is sent (from the stay on the Hotel Desk, or the line on the Activities desk) or recorded as sent another way.</div>
                    </div>
                    <a class="btn btn-light btn-sm" href="<?= base_url('tour/hotel_bookings') ?>"><i class="bi bi-arrow-left"></i> Hotel Bookings</a>
                </div>
                <div class="tf-card">
                    <div class="tf-card-h">
                        <div class="d-flex gap-2 flex-wrap" id="sm_chips">
                            <button class="chip on" data-state="all">All mails</button>
                            <button class="chip red" data-state="not_sent">Not sent <span id="sm_n"></span></button>
                            <button class="chip" data-state="sent">Sent</button>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="help" id="sm_tour"></span>
                            <input class="form-control form-control-sm" id="sm_q" placeholder="Tour, inquiry, hotel, restaurant, address…" style="width:260px">
                        </div>
                    </div>
                    <div class="tf-card-b" id="sm_list"><div class="help">Loading…</div></div>
                </div>
            </div>
        </div>
        <?= view('layouts/inpage_footer') ?>
    </div>
</div>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script src="<?= base_url('assets/tourmail/tour-mails.js') ?>?r1"></script>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url('tour/hotel_bookings') ?>';
    const P = new URLSearchParams(location.search);
    let state = ['all', 'sent', 'not_sent'].includes(P.get('state')) ? P.get('state') : 'all';
    const tourId = P.get('tour_id') || '';
    let timer = null;
    async function load() {
        const q = document.getElementById('sm_q').value.trim();
        const r = await fetch(`${BASE}/mails/data?state=${state}&tour_id=${encodeURIComponent(tourId)}&q=${encodeURIComponent(q)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const j = await r.json();
        const box = document.getElementById('sm_list');
        if (j.status !== 200) { box.innerHTML = `<div class="help" style="color:#b23a32">${window.AvhTourMails.esc(j.message || 'Could not read the mails.')}</div>`; return; }
        document.getElementById('sm_n').textContent = j.data.unsent ? '(' + j.data.unsent + ')' : '';
        box.innerHTML = window.AvhTourMails.listHtml(j.data.mails, BASE, true).replace('No hotel or restaurant mail on this tour yet.', state === 'not_sent' ? 'Nothing waiting: every mail went, or was recorded as sent another way.' : 'No mail found.');
        if (tourId && j.data.mails.length) document.getElementById('sm_tour').innerHTML = `Tour <b>${window.AvhTourMails.esc(j.data.mails[0].tour_no)}</b> only · <a href="${BASE}/mails?state=${state}">all tours</a>`;
    }
    document.querySelectorAll('#sm_chips [data-state]').forEach(b => {
        b.classList.toggle('on', b.dataset.state === state);
        b.addEventListener('click', () => { state = b.dataset.state; document.querySelectorAll('#sm_chips [data-state]').forEach(x => x.classList.toggle('on', x === b)); load(); });
    });
    document.getElementById('sm_q').addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 300); });
    load();
})();
</script>
</body>
</html>
