<?= view('layouts/head') ?>
<?php
/**
 * tf-appdesk-r1 (25 Oct 2026) — the Approver Desk page (home/approver_desk), to the approved mockup: two large cards — Payment
 * Approval (purple) and Hotel Rate Approval (green) — each opening its EXISTING page with the same URL and privilege, and the
 * note that approvals are controlled by role permissions and recorded in the audit trail. Back returns to Home. Navigation only.
 */
$esc = static fn ($s) => esc((string) $s);
$cards = $ad_cards ?? [];
?>
<body class="adk-body">
<style>
    :root { --ad-p:#842484; --ad-g:#78b848; --ad-gold:#e0a526; --ad-ink:#2b2b33; --ad-mut:#6b6f7b; --ad-line:#e8e3df; --ad-font:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
    body.adk-body { background:#fff !important; color:var(--ad-ink); font-family:var(--ad-font); }
    .tdk-top { position:sticky; top:0; z-index:1030; display:flex; align-items:center; gap:14px; padding:10px 28px; }
    .tdk-top .sp { flex:1; }
    .tdk-top .hd-user { display:flex; align-items:center; gap:9px; font-weight:600; color:#fff; }
    .tdk-top .hd-user img { width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--ad-gold); }
    .tdk-top .hd-user small { display:block; font-weight:400; font-size:.75rem; line-height:1.1; color:rgba(255,255,255,.8); }
    .tdk-top .hd-avatar-fallback { font-size:34px; line-height:1; color:#fff; }
    .av-nav-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:38px; padding:0 15px; border-radius:10px; border:0; font-size:13.5px; font-weight:600; line-height:1; white-space:nowrap; cursor:pointer; }
    .av-nav-out { background:transparent; border:1.5px solid rgba(255,255,255,.55); color:#fff !important; }

    .adk { max-width:1320px; margin:0 auto; padding:22px clamp(14px,2.4vw,30px) 36px; }
    .adk-head { display:flex; align-items:center; gap:18px; margin:4px 0 22px; }
    .adk-head .ic { width:86px; height:86px; border-radius:18px; background:rgba(132,36,132,.1); color:var(--ad-p); display:grid; place-items:center; font-size:42px; flex:0 0 auto; }
    .adk-head h1 { margin:0; font-size:clamp(1.7rem,3vw,2.6rem); font-weight:800; letter-spacing:-.02em; color:var(--ad-p); line-height:1.05; }
    .adk-head p { margin:4px 0 0; color:var(--ad-mut); font-size:1.1rem; }
    .adk-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:22px; }
    .adk-card { background:#fff; border:1px solid var(--ad-line); border-radius:18px; box-shadow:0 4px 16px rgba(20,20,30,.07); overflow:hidden; display:flex; flex-direction:column; transition:transform .2s ease, box-shadow .2s ease; }
    .adk-card:hover { transform:translateY(-3px); box-shadow:0 14px 34px rgba(20,20,30,.12); }
    .adk-card .band { height:14px; }
    .adk-card.purple .band { background:var(--ad-p); } .adk-card.green .band { background:var(--ad-g); }
    .adk-card .body { display:flex; align-items:center; gap:22px; padding:22px 26px 10px; }
    .adk-card .big { width:120px; height:120px; border-radius:50%; display:grid; place-items:center; font-size:58px; flex:0 0 auto; }
    .adk-card.purple .big { background:rgba(132,36,132,.1); color:var(--ad-p); } .adk-card.green .big { background:rgba(120,184,72,.18); color:#4d8a2a; }
    .adk-card .tx { min-width:0; }
    .adk-card .shield { width:40px; height:40px; border-radius:50%; background:rgba(224,165,38,.18); color:var(--ad-gold); display:grid; place-items:center; font-size:19px; margin-bottom:8px; }
    .adk-card h2 { margin:0; font-size:clamp(1.3rem,2vw,1.8rem); font-weight:800; letter-spacing:-.015em; color:var(--ad-ink); line-height:1.15; }
    .adk-card .sub { margin:6px 0 0; color:var(--ad-mut); font-size:1.02rem; }
    .adk-card .open { margin:14px 26px 24px; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 26px; border-radius:14px; color:#fff; font-weight:700; font-size:1.12rem; text-decoration:none !important; box-shadow:0 4px 12px rgba(0,0,0,.14); transition:filter .2s ease, transform .2s ease; }
    .adk-card.purple .open { background:var(--ad-p); } .adk-card.green .open { background:var(--ad-g); color:#1f2a14; }
    .adk-card .open:hover { filter:brightness(1.07); transform:translateY(-1px); }
    .adk-card .open:focus-visible { outline:3px solid var(--ad-gold); outline-offset:3px; }
    .adk-card .open i { font-size:1.2rem; font-weight:900; }
    .adk-note { margin-top:26px; display:flex; align-items:center; gap:22px; padding:22px 28px; border-radius:18px; background:rgba(132,36,132,.07); color:var(--ad-ink); font-size:1.1rem; }
    .adk-note .lk { width:76px; height:76px; border-radius:50%; background:rgba(132,36,132,.12); color:var(--ad-p); display:grid; place-items:center; font-size:34px; flex:0 0 auto; }
    .adk-note .sep { width:1px; align-self:stretch; background:rgba(132,36,132,.25); }
    .adk-foot { color:var(--ad-mut); font-size:.84rem; padding:12px 2px 0; }
    @media (max-width:991px) { .adk-grid { grid-template-columns:1fr; } }
    @media (max-width:640px) { .adk { padding:16px 12px 28px; } .adk-head .ic { width:62px; height:62px; font-size:30px; border-radius:14px; }
        .adk-card .body { gap:16px; padding:18px 18px 6px; } .adk-card .big { width:84px; height:84px; font-size:40px; } .adk-card .open { margin:12px 18px 18px; padding:14px 18px; font-size:1rem; }
        .adk-note { gap:14px; padding:16px; font-size:.98rem; } .adk-note .lk { width:52px; height:52px; font-size:24px; }
        .tdk-top { padding:8px 12px; gap:6px; } .tdk-top .hd-user span, .av-nav-out span { display:none; } }
    @media (prefers-reduced-motion:reduce) { .adk-card, .adk-card .open { transition:none; } }
</style>

<header class="tdk-top hd-top">
    <?= view('layouts/nav_bar', ['nb_back' => "window.location.href='" . base_url('home') . "'"]) ?>
    <div class="sp"></div>
    <?= view('layouts/page_search') ?>
    <div class="hd-user">
        <img src="<?= $esc($profile_picture ?? base_url('assets/images/users/no_profile.jpg')) ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'bi bi-person-circle hd-avatar-fallback'}))">
        <span><?= $esc($sidebar_employee_name ?? '') ?><small><?= $esc($sidebar_employee_name_profile_name ?? '') ?></small></span>
    </div>
    <button type="button" class="av-nav-btn av-nav-out" onclick="logout()" title="Logout" aria-label="Logout"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></button>
</header>

<main class="adk">
    <div class="adk-head">
        <div class="ic" aria-hidden="true"><i class="bi bi-file-earmark-check"></i></div>
        <div><h1>Approver Desk</h1><p>Review and authorize requests</p></div>
    </div>

    <?php if (!$cards): ?>
    <div class="adk-note"><span class="lk"><i class="bi bi-shield-lock-fill"></i></span><span class="sep"></span><span>No access yet — the Super Admin assigns approval rights to this desk.</span></div>
    <?php else: ?>
    <div class="adk-grid">
        <?php foreach ($cards as $c): ?>
        <section class="adk-card <?= $esc($c['tone']) ?>" aria-label="<?= $esc($c['title']) ?>">
            <div class="band" aria-hidden="true"></div>
            <div class="body">
                <div class="big" aria-hidden="true"><i class="bi <?= $esc($c['icon']) ?>"></i></div>
                <div class="tx">
                    <div class="shield" aria-hidden="true"><i class="bi bi-shield-fill"></i></div>
                    <h2><?= $esc($c['title']) ?></h2>
                    <?php if ($c['sub'] !== ''): ?><p class="sub"><?= $esc($c['sub']) ?></p><?php endif; ?>
                </div>
            </div>
            <a class="open" href="<?= $esc(base_url($c['url'])) ?>"><span><?= $esc($c['btn']) ?></span><i class="bi bi-chevron-right"></i></a>
        </section>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="adk-note" role="note">
        <span class="lk" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></span>
        <span class="sep" aria-hidden="true"></span>
        <span>Approvals are controlled by role permissions and recorded in the audit trail.</span>
    </div>
    <p class="adk-foot">Each card opens the existing approval page with its own URL, privilege and records; nothing is approved from this page.</p>
</main>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    if (typeof window.logout !== 'function') {
        window.logout = function () {
            Swal.fire({ title: 'Are you sure?', text: 'You want to logout from the system?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#78b848', cancelButtonColor: '#d33', confirmButtonText: 'Yes, Logout!' }).then(r => {
                if (!r.isConfirmed) return;
                $.ajax({ url: '<?= base_url() ?>logout', method: 'GET', success: function (res) { if (res.status == 200) { localStorage.removeItem('previousNotifications'); window.location.href = '<?= session()->get('is_admin') ?>' == 1 ? '<?= base_url() ?>admin' : '<?= base_url() ?>'; } } });
            });
        };
    }
})();
</script>
</body>
</html>
