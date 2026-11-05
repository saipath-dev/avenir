<?= view('layouts/head') ?>
<?php
/**
 * tf-findesk-r1 (25 Oct 2026) — the Finance Desk page (home/finance_desk), to the approved mockup: four tinted sections of compact
 * buttons — Payments & Payables, Tour Payment Monitoring (with the note that Payment Approval is handled in the Approver Desk),
 * Banking & Reconciliation, Accounting & Finance ("Finance Masters" folds its pages open in place). Every button opens an EXISTING
 * page the sign-in holds; Back returns to Home. Navigation only — nothing behind a button changes, no approval route is offered here.
 */
$esc = static fn ($s) => esc((string) $s);
$sections = $fd_sections ?? [];
?>
<body class="fdk-body">
<style>
    :root { --fd-p:#842484; --fd-g:#78b848; --fd-gold:#e0a526; --fd-ink:#2b2b33; --fd-mut:#6b6f7b; --fd-line:#e8e3df; --fd-font:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
    body.fdk-body { background:#fff !important; color:var(--fd-ink); font-family:var(--fd-font); }
    .tdk-top { position:sticky; top:0; z-index:1030; display:flex; align-items:center; gap:14px; padding:10px 28px; }
    .tdk-top .sp { flex:1; }
    .tdk-top .hd-user { display:flex; align-items:center; gap:9px; font-weight:600; color:#fff; }
    .tdk-top .hd-user img { width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--fd-gold); }
    .tdk-top .hd-user small { display:block; font-weight:400; font-size:.75rem; line-height:1.1; color:rgba(255,255,255,.8); }
    .tdk-top .hd-avatar-fallback { font-size:34px; line-height:1; color:#fff; }
    .av-nav-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:38px; padding:0 15px; border-radius:10px; border:0; font-size:13.5px; font-weight:600; line-height:1; white-space:nowrap; cursor:pointer; }
    .av-nav-out { background:transparent; border:1.5px solid rgba(255,255,255,.55); color:#fff !important; }

    .fdk { position:relative; max-width:1320px; margin:0 auto; padding:22px clamp(14px,2.4vw,30px) 36px; overflow:hidden; }
    .fdk .deco { position:absolute; right:-40px; top:-10px; width:360px; height:200px; pointer-events:none; }
    .fdk .deco .ball { position:absolute; left:0; top:36px; width:52px; height:52px; border-radius:50%; background:rgba(224,165,38,.3); }
    .fdk .deco .dots { position:absolute; left:120px; top:34px; width:110px; height:50px; background-image:radial-gradient(rgba(132,36,132,.35) 2px, transparent 2.5px); background-size:18px 18px; }
    .fdk .deco .arc { position:absolute; right:-60px; top:70px; width:300px; height:300px; border-radius:50%; background:rgba(132,36,132,.07); }
    .fdk-head { position:relative; margin:0 0 20px; }
    .fdk-head h1 { margin:0; font-size:clamp(1.7rem,3vw,2.6rem); font-weight:800; letter-spacing:-.02em; color:var(--fd-p); line-height:1.05; }
    .fdk-head p { margin:4px 0 0; color:var(--fd-mut); font-size:1.05rem; }
    .fdk-head .bar { width:66px; height:5px; border-radius:3px; background:var(--fd-gold); margin-top:10px; }
    .fdk-head .n { position:absolute; right:0; bottom:0; color:var(--fd-mut); font-size:.82rem; font-weight:600; }
    .fdk-grid { position:relative; display:grid; grid-template-columns:minmax(0,1.13fr) minmax(0,1fr); gap:18px; }
    .fdk-sec { border-radius:18px; padding:16px 18px 18px; border:1px solid transparent; box-shadow:0 2px 10px rgba(20,20,30,.04); }
    .fdk-sec.purple { background:rgba(132,36,132,.08); } .fdk-sec.gold { background:rgba(224,165,38,.13); } .fdk-sec.green { background:rgba(120,184,72,.15); } .fdk-sec.slate { background:#f1eef3; }
    .fdk-sec > h2 { margin:0 0 12px; display:flex; align-items:center; gap:14px; font-size:1.25rem; font-weight:800; color:var(--fd-p); letter-spacing:-.01em; }
    .fdk-sec > h2 .ic { width:50px; height:50px; border-radius:50%; display:grid; place-items:center; font-size:22px; color:#fff; flex:0 0 auto; box-shadow:0 3px 10px rgba(0,0,0,.12); }
    .fdk-sec.purple > h2 .ic { background:var(--fd-p); } .fdk-sec.gold > h2 .ic { background:var(--fd-gold); color:#2b2108; } .fdk-sec.green > h2 .ic { background:var(--fd-g); color:#1f2a14; } .fdk-sec.slate > h2 .ic { background:#5b3f6b; }
    .fdk-btns { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .fdk-sec.three .fdk-btns { grid-template-columns:repeat(3,minmax(0,1fr)); }
    .fdk-sec.one .fdk-btns { grid-template-columns:1fr; }
    .fdk-btn { display:flex; align-items:center; gap:12px; min-height:62px; padding:10px 12px 10px 14px; border-radius:14px; background:#fff; border:1px solid rgba(20,20,30,.06); color:var(--fd-ink); text-decoration:none !important; font-weight:700; font-size:.98rem; line-height:1.2; cursor:pointer; text-align:left; width:100%;
      box-shadow:0 2px 6px rgba(20,20,30,.05); transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
    .fdk-btn:hover { transform:translateY(-2px); box-shadow:0 10px 22px rgba(132,36,132,.14); border-color:rgba(132,36,132,.3); color:var(--fd-ink); }
    .fdk-btn:focus-visible { outline:3px solid var(--fd-gold); outline-offset:2px; }
    .fdk-btn .ic { width:36px; height:36px; display:grid; place-items:center; font-size:24px; color:var(--fd-p); flex:0 0 auto; position:relative; }
    .fdk-btn .ic .dot { position:absolute; right:-2px; bottom:-2px; width:15px; height:15px; border-radius:50%; background:var(--fd-gold); border:2px solid #fff; }
    .fdk-btn .ic .dot.g { background:var(--fd-g); }
    .fdk-btn .ch { margin-left:auto; color:var(--fd-mut); font-size:.95rem; transition:transform .2s ease; }
    .fdk-btn[aria-expanded="true"] .ch { transform:rotate(90deg); color:var(--fd-p); }
    .fdk-btn[aria-expanded="true"] { border-color:var(--fd-p); box-shadow:inset 0 0 0 1px var(--fd-p); }
    .fdk-btn small { display:block; font-weight:500; color:var(--fd-mut); font-size:.75rem; margin-top:1px; }
    .fdk-sub { grid-column:1 / -1; display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; padding:10px 12px 12px; border:1px dashed rgba(132,36,132,.4); border-radius:14px; background:rgba(255,255,255,.6); }
    .fdk-sub[hidden] { display:none; }
    .fdk-sub .t { grid-column:1 / -1; font-size:.74rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; color:var(--fd-p); }
    .fdk-sub .fdk-btn { min-height:48px; font-size:.9rem; }
    .fdk-note { margin-top:12px; display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:12px; background:rgba(255,255,255,.75); border:1px dashed rgba(224,165,38,.8); color:var(--fd-ink); font-size:.95rem; }
    .fdk-note i { width:26px; height:26px; border-radius:50%; border:2px solid var(--fd-gold); color:var(--fd-gold); display:grid; place-items:center; font-size:14px; font-style:normal; font-weight:800; flex:0 0 auto; }
    .fdk-foot { color:var(--fd-mut); font-size:.84rem; padding:14px 2px 0; }
    @media (max-width:1100px) { .fdk-grid { grid-template-columns:1fr; } .fdk-sec.three .fdk-btns { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:640px) { .fdk { padding:16px 12px 28px; } .fdk-btns, .fdk-sec.three .fdk-btns, .fdk-sub { grid-template-columns:1fr; } .fdk-head .n { display:none; } .fdk .deco { display:none; }
        .fdk-sec > h2 { font-size:1.1rem; } .fdk-sec > h2 .ic { width:42px; height:42px; font-size:19px; }
        .tdk-top { padding:8px 12px; gap:6px; } .tdk-top .hd-user span, .av-nav-out span { display:none; } }
    @media (prefers-reduced-motion:reduce) { .fdk-btn { transition:none; } }
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

<main class="fdk">
    <div class="deco" aria-hidden="true"><span class="ball"></span><span class="dots"></span><span class="arc"></span></div>
    <div class="fdk-head">
        <h1>Finance Desk</h1>
        <p>Payments, banking and accounts</p>
        <div class="bar"></div>
        <span class="n"><?= (int) ($fd_total ?? 0) ?> finance pages on this sign-in</span>
    </div>

    <?php if (!$sections): ?>
    <div class="fdk-sec slate"><h2><span class="ic"><i class="bi bi-wallet2"></i></span> Finance Desk</h2><div class="fdk-foot">No access yet — the Super Admin assigns access to the Finance Desk.</div></div>
    <?php endif; ?>

    <div class="fdk-grid">
        <?php foreach ($sections as $key => $sec):
            $n = 0; foreach ($sec['items'] as $it) { $n++; }
            $cols = $n === 1 ? ' one' : ''; ?>
        <section class="fdk-sec <?= $esc($sec['tone']) ?><?= $cols ?>" aria-labelledby="fdk-h-<?= $esc($key) ?>">
            <h2 id="fdk-h-<?= $esc($key) ?>"><span class="ic"><i class="bi <?= $esc($sec['icon']) ?>"></i></span><?= $esc($sec['title']) ?></h2>
            <div class="fdk-btns">
                <?php foreach ($sec['items'] as $it): ?>
                    <?php if (isset($it['group'])): $gid = 'fdk-g-' . $esc($it['group']); ?>
                    <button type="button" class="fdk-btn" aria-expanded="false" aria-controls="<?= $gid ?>" data-group="<?= $gid ?>">
                        <span class="ic"><i class="bi <?= $esc($it['icon']) ?>"></i></span>
                        <span><?= $esc($it['label']) ?><small><?= count($it['members']) ?> pages</small></span>
                        <i class="bi bi-chevron-right ch"></i>
                    </button>
                    <div class="fdk-sub" id="<?= $gid ?>" hidden>
                        <span class="t"><?= $esc($it['label']) ?></span>
                        <?php foreach ($it['members'] as $m): ?>
                        <a class="fdk-btn" href="<?= $esc(base_url($m['url'])) ?>"><span class="ic"><i class="bi <?= $esc($m['icon']) ?>"></i></span><span><?= $esc($m['label']) ?></span><i class="bi bi-chevron-right ch"></i></a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <a class="fdk-btn" href="<?= $esc(base_url($it['url'])) ?>">
                        <span class="ic"><i class="bi <?= $esc($it['icon']) ?>"></i><?php if (str_contains($it['label'], 'Finalized')): ?><span class="dot g"></span><?php elseif (in_array($it['label'], ['Payment Desk', 'Supplier Payables', 'Bank Payment Check', 'Voucher Payments'], true)): ?><span class="dot"></span><?php endif; ?></span>
                        <span><?= $esc($it['label']) ?></span>
                        <i class="bi bi-chevron-right ch"></i>
                    </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($sec['note'])): ?>
            <div class="fdk-note" role="note"><i>i</i><span><?= $esc($sec['note']) ?></span></div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    </div>
    <p class="fdk-foot">The payment workflow is unchanged: Finance prepares the payment → the Approver Desk approves or rejects it → Bank Payment Check handles the bank authorisation → Finalized Payments. Approval is not offered on this desk.</p>
</main>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
(function () {
    'use strict';
    document.querySelectorAll('.fdk-btn[data-group]').forEach(btn => {
        const panel = document.getElementById(btn.dataset.group);
        btn.addEventListener('click', () => {
            const open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', open ? 'false' : 'true');
            panel.hidden = open;
        });
    });
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
