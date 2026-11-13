<?= view('layouts/head') ?>
<?php
/**
 * tf-taxiq-r3 (3 Nov 2026) — Pilot → Pricing → Car rate approvals (tour/pricing/taxi_approvals).
 * A van taking a small tour at the car rate is approved here, by somebody other than the person who asked.
 * Until it is approved the tour cannot be allocated to that driver. Approving changes no price and allocates
 * nobody: it records that the agreed rate may be used.
 */
?>
<style>
    .ta { --ink:#1d2733; --muted:#5f6f80; --line:#e3e7ee; --p:#842484; --p-soft:rgba(132,36,132,.08);
          --g:#78b848; --g-soft:#eef7e4; --g-ink:#3f6b1f; --amber-soft:#ffffff; --amber:#1a1a1a; --red:#8a1c30; color:var(--ink); font-size:14px; }
    .ta .band { background:var(--p); color:#fff; border-radius:14px; padding:18px 22px; margin-bottom:18px; }
    .ta .band .eyebrow { font-size:11.5px; letter-spacing:.09em; text-transform:uppercase; opacity:.85; font-weight:700; }
    .ta .band h4 { margin:6px 0 4px; font-weight:800; font-size:24px; color:#fff; }
    .ta .band .sub { font-size:13.5px; opacity:.93; max-width:860px; line-height:1.55; }
    .ta .card2 { background:#fff; border:1px solid var(--line); border-radius:14px; margin-bottom:18px; box-shadow:0 1px 3px rgba(16,24,40,.05); }
    .ta .card2 > .hd { padding:16px 20px 12px; } .ta .card2 > .hd h6 { margin:0; font-weight:800; font-size:17px; color:var(--p); }
    .ta .card2 > .bd { padding:4px 20px 20px; }
    .ta .req { border:1px solid var(--line); border-radius:12px; padding:16px; margin-bottom:12px; }
    .ta .req.done { background:#fbfcfe; }
    .ta .pill { display:inline-block; border-radius:999px; padding:3px 10px; font-size:11px; font-weight:800; letter-spacing:.04em; }
    .ta .p-wait { background:var(--amber-soft); border:1px solid #d9d3dd; color:var(--amber); }
    .ta .p-ok { background:var(--g-soft); color:var(--g-ink); }
    .ta .p-no { background:#fbe9e7; color:var(--red); }
    .ta .btn-g { background:var(--g); border:0; color:#fff; font-weight:700; border-radius:10px; padding:9px 18px; font-size:14px; }
    .ta .btn-q { background:#fff; border:1px solid var(--line); color:var(--ink); font-weight:600; border-radius:10px; padding:9px 18px; font-size:14px; }
    .ta .help { color:var(--muted); font-size:12.5px; line-height:1.55; }
    .ta label.lb { font-size:12px; color:var(--muted); font-weight:600; display:block; margin-bottom:4px; }
    .ta .big { font-size:17px; font-weight:800; }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content ta" id="ta">
            <div class="container-fluid">
                <div class="band">
                    <div class="eyebrow">Pilot · Pricing</div>
                    <h4>Car rate approvals</h4>
                    <div class="sub">A van taking a small tour at the car rate needs the driver's agreement and an approval here, by somebody other than the person who asked. Until it is approved the tour cannot be allocated to that driver. Approving changes no price and allocates nobody — it records that the agreed rate may be used.</div>
                </div>
                <div class="card2">
                    <div class="hd"><h6>Requests</h6></div>
                    <div class="bd" id="list"><div class="help">Loading…</div></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url('tour/pricing/taxi_approvals') ?>';
    const esc = s => String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    const money = v => v === null || v === undefined || v === '' ? '—' : 'Rs. ' + Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const STATE = { requested: ['WAITING FOR APPROVAL', 'p-wait'], approved: ['APPROVED', 'p-ok'], declined: ['REFUSED', 'p-no'], cancelled: ['CANCELLED', 'p-no'] };

    async function load() {
        const r = await fetch(BASE + '/data', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const j = await r.json();
        const box = document.getElementById('list');
        if (j.status !== 200) { box.innerHTML = '<div class="help">' + esc(j.message || 'These approvals could not be read.') + '</div>'; return; }
        const rs = j.data.requests || [];
        if (!rs.length) { box.innerHTML = '<div class="help">Nothing is waiting. A request appears here when a van driver agrees to take a small tour at the car rate.</div>'; return; }
        box.innerHTML = rs.map(r => {
            const [label, cls] = STATE[r.state] || ['—', 'p-wait'];
            const mine = String(r.requested_by_id || '') === String(j.data.me_id || '');
            const open = r.state === 'requested';
            return `<div class="req ${open ? '' : 'done'}">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div class="big">${esc(r.person_name || '')} — ${money(r.agreed_rate_km)}<span class="help"> per km</span></div>
                    <span class="pill ${cls}">${esc(label)}</span>
                </div>
                <div class="help">${r.tour_no ? esc(r.tour_no) + ' · ' : ''}${r.guests ? esc(r.guests) + ' passengers · ' : ''}normal van rate ${money(r.van_rate_km)} · car rate on his record ${money(r.car_rate_km)}</div>
                <div class="help">asked by ${esc(r.requested_by || '')} on ${esc(String(r.requested_at || '').slice(0, 16))}${r.driver_said ? ' — “' + esc(r.driver_said) + '”' : ''}</div>
                ${r.decided_at ? `<div class="help">${r.state === 'approved' ? 'approved' : 'refused'} by ${esc(r.decided_by || '')} on ${esc(String(r.decided_at).slice(0, 16))}${r.decision_note ? ' — ' + esc(r.decision_note) : ''}</div>` : ''}
                ${open ? `<div class="d-flex flex-wrap gap-2 align-items-end mt-3">
                    ${mine ? '<span class="help">You asked for this one, so somebody else approves it.</span>'
                           : `<div style="flex:1;min-width:240px"><label class="lb">Note (kept with the decision)</label><input class="form-control" id="n_${r.id}"></div>
                              <button class="btn-g" data-ap="${r.id}">Approve</button><button class="btn-q" data-rf="${r.id}">Refuse</button>`}
                </div><div id="m_${r.id}" class="mt-2" style="font-size:13px;font-weight:700"></div>` : ''}
            </div>`;
        }).join('');
        wire();
    }

    function wire() {
        const go = (id, yes, btn) => {
            const say = (t, bad) => { const el = document.getElementById('m_' + id); if (el) { el.textContent = t || ''; el.style.color = bad ? '#8a1c30' : '#3f6b1f'; } };
            // confirmed in place — no browser dialog, and a double-click cannot decide
            if (btn.dataset.armed === '1' && Date.now() - Number(btn.dataset.at || 0) < 500) { return; }
            if (btn.dataset.armed !== '1') {
                btn.dataset.armed = '1'; btn.dataset.at = String(Date.now()); btn.dataset.was = btn.textContent;
                btn.textContent = yes ? 'Yes — approve it' : 'Yes — refuse it';
                say(yes ? 'This approves the car rate; the tour may then be allocated to him.' : 'This refuses it; he keeps his place in the queue.');
                setTimeout(() => { if (btn.dataset.armed === '1') { btn.dataset.armed = ''; btn.textContent = btn.dataset.was; say(''); } }, 10000);
                return;
            }
            btn.dataset.armed = ''; btn.textContent = btn.dataset.was; btn.disabled = true;
            fetch(BASE + '/' + id, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                     body: JSON.stringify({ approve: yes, note: (document.getElementById('n_' + id) || {}).value || '' }) })
                .then(r => r.json()).then(j => { btn.disabled = false; say(j.message || 'Recorded.', j.status !== 200); if (j.status === 200) { setTimeout(load, 1500); } })
                .catch(() => { btn.disabled = false; say('That did not reach the server. Nothing was changed.', true); });
        };
        document.querySelectorAll('[data-ap]').forEach(b => b.addEventListener('click', () => go(b.dataset.ap, true, b)));
        document.querySelectorAll('[data-rf]').forEach(b => b.addEventListener('click', () => go(b.dataset.rf, false, b)));
    }
    load();
})();
</script>
</body>
</html>
