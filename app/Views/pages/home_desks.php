<?= view('layouts/head') ?>
<?php
/**
 * tf-home-r4 (22 Oct 2026): layout of the approved design proposal (tall painting panel, centred heading, two desk rows, link cards).
 * tf-home-r1 — Home: the Avenir Holidays Handling System dashboard (five desks). Controller: HomeDeskController.
 * Every link here is an existing screen; each desk lists only what the signed-in group may open (HomeDesks::forUser),
 * and the route filter still checks every page when it is opened. The logo and the painting are shown whole.
 */
$home = $home ?? ['desks' => [], 'links' => []];
$esc = static fn ($s) => esc((string) $s);
$deskIcon = [
    'tour' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M22 40c-2-9-1-17 3-24"/><path d="M25 16c-5-4-12-3-15 1 5-1 10 0 15-1z" fill="currentColor"/><path d="M25 16c3-6 10-8 15-5-5 1-10 3-15 5z" fill="currentColor"/><path d="M25 16c1-6-2-10-6-12 2 4 3 8 6 12z" fill="currentColor"/><path d="M6 41c8-4 28-4 36 0"/></svg>',
    'reservation' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><rect x="6" y="9" width="30" height="28" rx="4"/><path d="M6 17h30M14 5v8M28 5v8"/><circle cx="35" cy="35" r="8"/><path d="m31 35 3 3 5-6"/></svg>',
    'transport' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><rect x="9" y="6" width="30" height="32" rx="6"/><path d="M9 24h30M14 13h20"/><circle cx="16" cy="31" r="2" fill="currentColor"/><circle cx="32" cy="31" r="2" fill="currentColor"/><path d="M14 38v4M34 38v4"/></svg>',
    'finance' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><ellipse cx="30" cy="12" rx="11" ry="4.5"/><path d="M19 12v14c0 2.5 5 4.5 11 4.5S41 28.5 41 26V12M19 19c0 2.5 5 4.5 11 4.5S41 21.5 41 19"/><ellipse cx="17" cy="30" rx="11" ry="4.5"/><path d="M6 30v7c0 2.5 5 4.5 11 4.5S28 39.5 28 37v-7"/></svg>',
    'masters' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 14a3 3 0 0 1 3-3h9l4 4h17a3 3 0 0 1 3 3v19a3 3 0 0 1-3 3H9a3 3 0 0 1-3-3z"/><path d="M6 20h36M15 27h18M15 33h12"/></svg>',
    'approver' => '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M28 5H11a3 3 0 0 0-3 3v32a3 3 0 0 0 3 3h12"/><path d="M28 5l8 8v9M28 5v8h8M14 17h12M14 24h10M14 31h6"/><circle cx="34" cy="35" r="8"/><path d="m30 35 3 3 5-6"/></svg>',
];
$item = static function (array $it) use ($esc): string {
    return '<a class="hd-it" role="menuitem" href="' . $esc(base_url($it['url'])) . '"><i class="bi ' . $esc($it['icon'] ?? 'bi-dot') . '"></i><span><b>'
        . $esc($it['label']) . '</b>' . (!empty($it['sub']) ? '<small>' . $esc($it['sub']) . '</small>' : '') . '</span></a>';
};
?>
<body class="hd-body">
<style>
    /* tf-home-r4 (22 Oct 2026) — the approved design proposal: a tall painting panel on the left, from just below the
       header bar to the foot of the page; logo, title, the two desk rows and the link cards centred on the right. */
    :root { --hd-g1:#78b848; --hd-g2:#78b848; --hd-b1:#842484; --hd-b2:#842484; --hd-db1:#842484; --hd-db2:#842484; --hd-title:#842484;
            --hd-gold:#c9952f; --hd-ink:#2b2b33; --hd-mut:#6b6f7b; --hd-line:#ece4da; --hd-cream:#fbf7f1; --hd-font:"Inter Tight","Inter","Segoe UI",system-ui,sans-serif; }
    body.hd-body { background:var(--hd-cream) !important; color:var(--hd-ink); }
    .hd-top { position:sticky; top:0; z-index:1030; display:flex; align-items:center; gap:14px; padding:10px 28px; }
    .hd-top .sp { flex:1; }
    .hd-top .hd-user { display:flex; align-items:center; gap:9px; font-weight:600; }
    .hd-top .hd-user img { width:34px; height:34px; border-radius:50%; object-fit:cover; border:2px solid var(--hd-gold); }
    .hd-top .hd-user small { display:block; font-weight:400; font-size:.75rem; line-height:1.1; }
    .hd-top .hd-avatar-fallback { font-size:34px; line-height:1; }
    .hd-top .avbar-link[aria-current="page"] { background:transparent; border-radius:0; box-shadow:inset 0 -3px 0 var(--hd-gold); }   /* Home: gold underline */

    .hd-wrap { display:grid; grid-template-columns:clamp(250px,26vw,400px) minmax(0,1fr); gap:clamp(28px,3.4vw,52px);
      max-width:1500px; margin:0 auto; padding:24px clamp(16px,2.6vw,40px) 28px; align-items:stretch;
      min-height:min(calc(100vh - 62px), calc(clamp(250px,26vw,400px) * 2.75));   /* the panel runs to the foot of the screen; never taller than the canvas allows */
      background:radial-gradient(1200px 520px at 70% 0%, #fff 0%, rgba(255,255,255,0) 70%); }
    /* the painting panel: slim gold frame, softly rounded, subtle shadow; the painting whole, sky extended above and below */
    .hd-frame { margin:0; display:flex; padding:5px; background:#fff; border:1.5px solid var(--hd-gold); border-radius:20px;
      box-shadow:0 2px 4px rgba(70,30,20,.06),0 16px 36px rgba(70,30,20,.13); }
    .hd-art { position:relative; flex:1; min-height:clamp(250px,26vw,400px); border-radius:15px; overflow:hidden; background:#121638; }
    .hd-art img { position:absolute; inset:0; width:100%; height:100%; display:block; object-fit:cover; object-position:50% 50%; }
    .hd-art.sq img { object-fit:contain; }   /* fallback: the original square painting, whole, on night blue */
    .hd-main { min-width:0; display:flex; flex-direction:column; justify-content:flex-start; padding:0 0 4px; }   /* tf-home-r5: the desks sit higher, in line with the painting */
    .hd-brand { text-align:center; margin:0 0 6px; }
    .hd-brand img { height:clamp(72px,7.4vw,104px); max-width:100%; width:auto; }
    .hd-title { font-family:var(--hd-font); color:var(--hd-title); text-align:center; font-size:clamp(1.8rem,3.1vw,2.75rem); font-weight:800; margin:6px 0 4px; letter-spacing:-.03em; line-height:1.08; }
    /* tf-stage2: the daily USD rate box */
    /* tf-usdpill-r1 (8 Nov 2026, Shantha — mockup option 3): the rate is a small pill in the top bar; the full box opens under it */
    .hd-usd { font-family:var(--hd-font); background:#fff; border:1.5px solid var(--hd-line); border-radius:16px; overflow:hidden; box-shadow:0 18px 40px rgba(43,27,48,.22);
      position:fixed; top:64px; right:clamp(12px,14vw,330px); width:min(460px, calc(100vw - 24px)); z-index:1045; display:none; }
    .hd-usd.open { display:block; }
    .hd-usd-x { background:transparent; border:0; color:#fff; font-size:1.1rem; line-height:1; cursor:pointer; padding:0 2px; margin-left:4px; }
    .hd-usdpill { display:inline-flex; align-items:center; gap:8px; height:38px; padding:0 6px 0 12px; border-radius:999px; border:1px solid rgba(255,255,255,.45);
      background:rgba(255,255,255,.14); color:#fff; font-family:var(--hd-font); font-size:.85rem; font-weight:600; cursor:pointer; white-space:nowrap; transition:background .15s; }
    .hd-usdpill:hover, .hd-usdpill[aria-expanded="true"] { background:rgba(255,255,255,.24); }
    .hd-usdpill b { font-size:.95rem; font-weight:800; font-variant-numeric:tabular-nums; letter-spacing:.01em; }
    .hd-usdpill .lb { opacity:.85; font-weight:600; }
    .hd-usdpill .st { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:3px 9px; font-size:.72rem; font-weight:700; }
    .hd-usdpill .st::before { content:""; width:7px; height:7px; border-radius:50%; background:currentColor; }
    .hd-usdpill .st.ok { background:#eaf5df; color:#4e8a26; } .hd-usdpill .st.warn { background:#fdf0d5; color:#9a6a12; }
    @media (max-width:991px) { .hd-usd { top:58px; right:12px; } .hd-usdpill .lb { display:none; } }
    @media (max-width:480px) { .hd-usdpill { padding:0 4px 0 9px; gap:5px; } .hd-usdpill .st { padding:3px 7px; } }
    .hd-usd.warn { border-color:#e5b45a; }
    .hd-usd-h { background:var(--hd-b1); color:#fff; padding:9px 16px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; font-weight:700; font-size:.95rem; }
    .hd-usd-h .st { font-size:.75rem; font-weight:700; border-radius:14px; padding:2px 10px; letter-spacing:.03em; text-transform:uppercase; }
    .hd-usd-h .st.ok { background:#eaf5df; color:#4e8a26; } .hd-usd-h .st.warn { background:#fdf0d5; color:#9a6a12; }
    .hd-usd-b { padding:12px 16px 14px; }
    .hd-usd-f { display:flex; gap:10px; align-items:center; flex-wrap:wrap; font-size:1.05rem; }
    .hd-usd-f input { width:140px; font-size:1.35rem; font-weight:700; padding:6px 12px; border:1.5px solid var(--hd-line); border-radius:10px; font-variant-numeric:tabular-nums; color:var(--hd-ink); }
    .hd-usd-f input:focus { outline:none; border-color:var(--hd-b1); box-shadow:0 0 0 3px rgba(132,36,132,.14); }
    .hd-usd-save { background:var(--hd-g1); color:#fff; border:0; border-radius:22px; padding:9px 20px; font-weight:700; font-size:.95rem; cursor:pointer; }
    .hd-usd-save:hover { background:#5f9a36; } .hd-usd-save:disabled { opacity:.6; cursor:default; }
    .hd-usd-ro { font-size:1.35rem; font-weight:700; font-variant-numeric:tabular-nums; }
    .hd-usd-note, .hd-usd-l { font-size:.85rem; color:var(--hd-mut); }
    .hd-usd-l { margin-top:8px; } .hd-usd.warn .hd-usd-l b { color:#9a6a12; }
    .hd-usd-reason { margin-top:8px; } .hd-usd-reason input { width:100%; max-width:520px; padding:7px 12px; border:1.5px solid #e5b45a; border-radius:10px; font-size:.95rem; }
    .hd-usd-msg { margin-top:8px; font-size:.9rem; font-weight:600; } .hd-usd-msg.err { color:#b23a32; } .hd-usd-msg.ok { color:#4e8a26; }
    .hd-sub { font-family:var(--hd-font); text-align:center; color:#4a4d57; font-size:clamp(1rem,1.35vw,1.25rem); font-weight:400; margin:0 0 clamp(16px,1.8vw,24px); }

    .hd-desks { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; }
    .hd-desk { position:relative; }
    .hd-desk > .hd-btn { width:100%; min-height:96px; border:0; border-radius:14px; color:#fff; text-align:left; padding:16px 16px 16px 18px; display:flex; align-items:center; gap:16px;
      box-shadow:0 6px 16px rgba(132,36,132,.16); transition:transform .15s, box-shadow .15s; cursor:pointer; font-family:var(--hd-font); }
    .hd-desk > .hd-btn:hover { transform:translateY(-2px); box-shadow:0 12px 24px rgba(132,36,132,.22); }
    .hd-desk > .hd-btn:focus-visible { outline:3px solid var(--hd-gold); outline-offset:3px; }
    .hd-desk.green > .hd-btn { background:linear-gradient(145deg,#78b848,#78b848); color:#2b2b33; }   /* tf-palette-r1: the logo green is light; dark lettering */
    .hd-desk.green > .hd-btn .ct { color:rgba(43,43,51,.78); }
    .hd-desk.green > .hd-btn .ic, .hd-desk.green > .hd-btn .chev { background:rgba(255,255,255,.38); box-shadow:inset 0 0 0 1px rgba(43,43,51,.12); }
    .hd-desk.green > .hd-btn:focus-visible { outline-color:#842484; }
    .hd-desk.burgundy > .hd-btn { background:linear-gradient(145deg,var(--hd-b1),var(--hd-b2)); }
    .hd-desk.darkburgundy > .hd-btn { background:linear-gradient(145deg,var(--hd-db1),var(--hd-db2)); }
    .hd-desk.none > .hd-btn { filter:saturate(.35) brightness(1.05); }
    .hd-btn .ic { flex:none; width:52px; height:52px; border-radius:50%; display:grid; place-items:center; background:rgba(255,255,255,.15); box-shadow:inset 0 0 0 1px rgba(255,255,255,.2); }
    .hd-btn .ic svg { width:27px; height:27px; }
    .hd-btn .row1 { flex:1; min-width:0; display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .hd-btn .tx { min-width:0; display:flex; flex-direction:column; line-height:1.15; }
    .hd-btn .nm { font-size:1.12rem; font-weight:700; letter-spacing:-.01em; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .hd-btn .ct { font-size:.84rem; font-weight:500; color:rgba(255,255,255,.85); margin-top:4px; }
    .hd-btn .chev { flex:none; width:30px; height:30px; border-radius:50%; display:grid; place-items:center; background:rgba(255,255,255,.16); font-size:.82rem; transition:background .15s; }
    .hd-btn .chev i { display:inline-block; transition:transform .15s; }
    .hd-desk > .hd-btn:hover .chev { background:rgba(255,255,255,.26); }
    .hd-desk.open .hd-btn .chev i { transform:rotate(90deg); }   /* chevron points right; down while the list is open */

    .hd-menu { display:none; position:absolute; left:0; right:0; top:calc(100% + 8px); z-index:1040; background:#fff; border-radius:14px; box-shadow:0 16px 38px rgba(40,20,25,.2); border:1px solid var(--hd-line); padding:6px; max-height:62vh; overflow:auto; }
    .hd-desk.open .hd-menu { display:block; }
    .hd-it { display:flex; gap:10px; align-items:flex-start; padding:8px 11px; border-radius:9px; color:var(--hd-ink); text-decoration:none; }
    .hd-it:hover, .hd-it:focus-visible { background:rgba(120,184,72,.18); color:var(--hd-ink); outline:none; }
    .hd-desk.burgundy .hd-it:hover, .hd-desk.burgundy .hd-it:focus-visible { background:#f9eef2; }
    .hd-it i { font-size:1rem; margin-top:2px; color:var(--hd-g2); }
    .hd-desk.burgundy .hd-it i { color:var(--hd-b1); }
    .hd-desk.darkburgundy .hd-it i { color:var(--hd-db1); }
    .hd-desk.darkburgundy .hd-it:hover, .hd-desk.darkburgundy .hd-it:focus-visible { background:rgba(132,36,132,.08); }
    .hd-it b { display:block; font-weight:600; font-size:.92rem; line-height:1.25; }
    .hd-it small { display:block; color:var(--hd-mut); font-size:.76rem; line-height:1.25; }
    .hd-grp { width:100%; border:0; display:flex; align-items:center; justify-content:space-between; gap:8px; padding:9px 12px; border-radius:9px; font-weight:700; color:#2b2b33; background:var(--hd-g1); margin:2px 0; }
    .hd-desk.burgundy .hd-grp, .hd-desk.darkburgundy .hd-grp { color:#fff; }
    .hd-desk.burgundy .hd-grp { background:var(--hd-b1); }
    .hd-grp .gl { display:inline-flex; gap:9px; align-items:center; }
    .hd-grp[aria-expanded="true"] .bi-chevron-right { transform:rotate(90deg); }
    .hd-sublist { margin:4px 0 6px 16px; border-left:2px solid rgba(132,36,132,.22); padding-left:6px; }
    .hd-none { color:var(--hd-mut); font-size:.85rem; padding:10px 12px; }

    /* the link cards below the desks, between two hairlines */
    .hd-links { display:flex; align-items:center; gap:18px; margin-top:clamp(26px,3vw,40px); }
    .hd-links::before, .hd-links::after { content:""; flex:1; height:1px; background:linear-gradient(90deg,rgba(155,27,64,0),rgba(155,27,64,.22)); }
    .hd-links::after { background:linear-gradient(90deg,rgba(155,27,64,.22),rgba(155,27,64,0)); }
    .hd-links .lk { display:flex; align-items:center; gap:18px; }
    .hd-links .lk > i { width:1px; height:34px; background:var(--hd-line); }
    .hd-links a { display:inline-flex; align-items:center; gap:12px; padding:12px 18px; border-radius:10px; border:1px solid #e3d6cc; background:#fff; color:var(--hd-title);
      font-family:var(--hd-font); font-weight:600; font-size:1rem; text-decoration:none; box-shadow:0 1px 2px rgba(132,36,132,.04); transition:background .15s, box-shadow .15s; white-space:nowrap; }
    .hd-links a > .bi:first-child { font-size:1.3rem; }
    .hd-links a .go { font-size:.8rem; color:#8a7f86; margin-left:6px; }
    .hd-links a:hover { background:rgba(132,36,132,.08); box-shadow:0 6px 16px rgba(132,36,132,.08); }
    .hd-foot { text-align:center; color:var(--hd-mut); font-size:.8rem; margin-top:14px; }

    @media (max-width:1439px) { .hd-desk > .hd-btn { gap:12px; padding:14px 12px 14px 14px; } .hd-btn .ic { width:44px; height:44px; } .hd-btn .ic svg { width:24px; height:24px; } .hd-btn .nm { font-size:1.02rem; } .hd-btn .chev { width:26px; height:26px; } .hd-desks { gap:16px; } }
    @media (max-width:1259px) { .hd-desks { grid-template-columns:repeat(2,minmax(0,1fr)); } .hd-desk { order:9; } .hd-desk[data-desk=tour] { order:1; } .hd-desk[data-desk=reservation] { order:2; } .hd-desk[data-desk=masters] { order:3; } .hd-desk[data-desk=transport] { order:4; } .hd-desk[data-desk=finance] { order:5; } .hd-desk[data-desk=approver] { order:6; } .hd-wrap { grid-template-columns:clamp(220px,24vw,280px) minmax(0,1fr); } }
    @media (max-width:991px) {
        .hd-top { padding:8px 12px; gap:6px; } .hd-top .hd-user span, .hd-top .hd-out span, .hd-top .av-nav-out span { display:none; }
        .hd-wrap { grid-template-columns:1fr; gap:24px; padding:16px 14px 26px; min-height:0; }
        .hd-main { order:1; } .hd-frame { order:2; width:min(260px,72vw); margin:0 auto; } .hd-art { flex:none; width:100%; aspect-ratio:9/16; min-height:0; }
        .hd-desks { grid-template-columns:1fr; gap:12px; }
        .hd-desk > .hd-btn { min-height:76px; padding:12px 14px; } .hd-btn .ic { width:46px; height:46px; } .hd-btn .ic svg { width:24px; height:24px; }
        .hd-menu { position:static; margin-top:8px; max-height:none; }
        .hd-links { flex-direction:column; gap:10px; } .hd-links::before, .hd-links::after, .hd-links .lk > i { display:none; } .hd-links .lk { flex-direction:column; gap:10px; width:100%; } .hd-links a { width:100%; justify-content:center; }
    }
    @media (min-width:600px) and (max-width:991px) { .hd-desks { grid-template-columns:repeat(2,minmax(0,1fr)); } }   /* two per row; Master Files stays under Tour Desk */
    @media (max-width:599px) { .hd-desk { order:0 !important; } }   /* one per row: the usual order */
    /* tf-tiles-r2 (8 Nov 2026, Shantha's picture): two columns of larger tiles — icon in a circle, a thin divider, name and
       subtitle, chevron in a circle — green and purple alternating like a chessboard. Every desk the person has stays. */
    .hd-desks { grid-template-columns:repeat(2,minmax(0,1fr)) !important; gap:18px 22px; max-width:1040px; margin:0 auto; }
    .hd-desk { order:0 !important; }
    .hd-desk > .hd-btn { min-height:118px; border-radius:16px; padding:18px 20px 18px 22px; gap:0; box-shadow:0 6px 16px rgba(43,27,48,.10); }
    .hd-desk:nth-child(4n+1) > .hd-btn, .hd-desk:nth-child(4n+4) > .hd-btn { background:linear-gradient(145deg,#7cba4c,#69a83e) !important; color:#fff !important; }
    .hd-desk:nth-child(4n+2) > .hd-btn, .hd-desk:nth-child(4n+3) > .hd-btn { background:linear-gradient(145deg,#8a2a8a,#7a1f7a) !important; color:#fff !important; }
    .hd-desk > .hd-btn .ct { color:rgba(255,255,255,.9) !important; font-size:1rem; margin-top:6px; }
    .hd-desk > .hd-btn .nm { font-size:1.42rem; font-weight:800; text-shadow:0 1px 1px rgba(0,0,0,.12); }
    .hd-desk > .hd-btn .ic { width:66px; height:66px; background:rgba(255,255,255,.88) !important; box-shadow:none !important; }
    .hd-desk:nth-child(4n+1) > .hd-btn .ic, .hd-desk:nth-child(4n+4) > .hd-btn .ic { color:#4f8a2a; }
    .hd-desk:nth-child(4n+2) > .hd-btn .ic, .hd-desk:nth-child(4n+3) > .hd-btn .ic { color:#7a1f7a; }
    .hd-desk > .hd-btn .ic svg { width:32px; height:32px; }
    .hd-desk > .hd-btn .row1 { margin-left:22px; padding-left:22px; border-left:1.5px solid rgba(255,255,255,.55); align-self:stretch; }
    .hd-desk > .hd-btn .chev { width:40px; height:40px; background:rgba(255,255,255,.22) !important; box-shadow:none !important; color:#fff; font-size:1rem; }
    .hd-desk > .hd-btn:hover .chev { background:rgba(255,255,255,.34) !important; }
    .hd-desk.none > .hd-btn { filter:saturate(.55) brightness(1.04); }
    @media (max-width:1259px) { .hd-desk > .hd-btn { min-height:104px; padding:16px; } .hd-desk > .hd-btn .ic { width:56px; height:56px; } .hd-desk > .hd-btn .row1 { margin-left:16px; padding-left:16px; } .hd-desk > .hd-btn .nm { font-size:1.22rem; } }
    @media (max-width:599px) { .hd-desks { grid-template-columns:1fr !important; gap:12px; }
        .hd-desk:nth-child(odd) > .hd-btn { background:linear-gradient(145deg,#7cba4c,#69a83e) !important; } .hd-desk:nth-child(even) > .hd-btn { background:linear-gradient(145deg,#8a2a8a,#7a1f7a) !important; }
        .hd-desk:nth-child(odd) > .hd-btn .ic { color:#4f8a2a; } .hd-desk:nth-child(even) > .hd-btn .ic { color:#7a1f7a; }
        .hd-desk > .hd-btn { min-height:84px; } .hd-desk > .hd-btn .ic { width:48px; height:48px; } .hd-desk > .hd-btn .nm { font-size:1.08rem; } .hd-desk > .hd-btn .ct { font-size:.88rem; } }
</style>

<style id="av-nav-btn-css">
/* tf-navbtn-r1 (22 Oct 2026): Back / Home / Dashboard - the Tour Inquiry button style (38px, 10px corners, soft shadow), solid colours */
.av-nav-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:38px; padding:0 15px; border-radius:10px; border:0;
  font-size:13.5px; font-weight:600; letter-spacing:.2px; line-height:1; white-space:nowrap; text-decoration:none; cursor:pointer;
  box-shadow:0 3px 10px rgba(0,0,0,.16); transition:filter .2s ease, box-shadow .2s ease, transform .2s ease; }
.av-nav-btn i { font-size:16px; line-height:1; }
.av-nav-btn:hover { filter:brightness(1.08); box-shadow:0 5px 14px rgba(0,0,0,.22); text-decoration:none; }
.av-nav-btn:active { transform:translateY(1px) scale(.98); }
.av-nav-btn:focus-visible { outline:3px solid rgba(224,165,38,.55); outline-offset:2px; }
.av-nav-back { background:#842484; color:#fff !important; }
.av-nav-home { background:#78b848; color:#2b2b33 !important; }
.av-nav-dash { background:#e0a526; color:#2b2108 !important; }
.av-nav-out  { background:#c62828; color:#fff !important; }
@media (max-width:767px) { .av-nav-btn { height:38px; padding:0 11px; font-size:12.5px; gap:5px; } }
@media (max-width:480px) { .av-nav-btn { width:42px; height:42px; padding:0; } .av-nav-btn i { font-size:18px; } .av-nav-btn span { display:none; } }
</style>
<header class="hd-top">
    <?= view('layouts/nav_bar', ['nb_back' => 'window.history.back()']) ?><?php /* tf-navbar-r1 */ ?>
    <div class="sp"></div>
    <?php if (!empty($usd)): // tf-usdpill-r1: today's USD rate as a pill; the details open under it
        $pRate = $usd['entered'] ? $usd['rate'] : $usd['last_rate'];
        if (!$usd['entered']) { $pSt = ['warn', $usd['last_date'] ? date('j M', strtotime($usd['last_date'])) : 'Not set', 'Not entered today']; }
        elseif (!empty($usd['test'])) { $pSt = ['warn', 'TEST', 'Today\'s figure is marked TEST']; }
        else { $pSt = ['ok', 'Today', 'Entered today']; } ?>
    <button type="button" class="hd-usdpill" id="hd_usd_pill" aria-expanded="false" aria-controls="hd_usd" title="Today's USD rate — <?= $esc($pSt[2]) ?>. Click for details.">
        <span class="lb">USD</span><b><?= $pRate !== null ? $esc(number_format($pRate, 2)) : '—' ?></b><span class="st <?= $pSt[0] ?>"><?= $esc($pSt[1]) ?></span>
    </button>
    <?php endif; ?>
    <?= view('layouts/page_search') ?><?php /* tf-search-r1 */ ?>
    <div class="hd-user">
        <img src="<?= $esc($profile_picture ?? base_url('assets/images/users/no_profile.jpg')) ?>" alt="" onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'bi bi-person-circle hd-avatar-fallback'}))">
        <span><?= $esc($sidebar_employee_name ?? '') ?><small><?= $esc($sidebar_employee_name_profile_name ?? '') ?></small></span>
    </div>
    <button type="button" class="av-nav-btn av-nav-out" onclick="logout()" title="Logout" aria-label="Logout"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></button>
</header>

<main class="hd-wrap">
    <figure class="hd-frame">
        <div class="hd-art<?= empty($home_painting_tall) ? ' sq' : '' ?>">
            <?php if (!empty($home_painting)): ?>
                <img src="<?= $esc($home_painting) ?>" alt="Office painting: the sun and the moon">
            <?php endif; ?>
        </div>
    </figure>

    <div class="hd-main">
    <?= view('partials/world_clock', ['layout' => 'brand', 'logo' => '<div class="hd-brand"><img src="' . base_url('assets/images/Logo-new.png') . '" alt="Avenir Holidays — Connecting Cultures"></div>']) ?><?php /* tf-clock-r3: Tokyo and Colombo either side of the logo */ ?>
    <h1 class="hd-title">Avenir Holidays Handling System</h1>
    <p class="hd-sub">Choose your desk to get started</p>

        <div class="hd-desks">
            <?php foreach ($home['desks'] as $d): $id = 'hd-' . $d['key']; $has = !empty($d['items']); ?>
                <div class="hd-desk <?= $esc($d['tone']) ?><?= $has ? '' : ' none' ?>" data-desk="<?= $esc($d['key']) ?>">
                    <?php $deskPage = ['tour' => 'home/tour_desk', 'reservation' => 'home/reservation_desk', 'transport' => 'home/transport_desk', 'masters' => 'home/master_files', 'finance' => 'home/finance_desk', 'approver' => 'home/approver_desk'][$d['key']] ?? null; /* tf-tourdesk-r1 / tf-resdesk-r1 / tf-transdesk-r1 / tf-masters-r1 / tf-findesk-r1: these desks open their own page */ ?>
                    <?php if ($deskPage): ?>
                    <a class="hd-btn" href="<?= base_url($deskPage) ?>" data-link="1" style="text-decoration:none;">
                    <?php else: ?>
                    <button type="button" class="hd-btn" aria-expanded="false" aria-controls="<?= $id ?>">
                    <?php endif; ?>
                        <span class="ic"><?= $deskIcon[$d['icon']] ?? '' ?></span>
                        <span class="row1"><span class="tx"><span class="nm"><?= $esc($d['name']) ?></span><span class="ct"><?php $n = 0; array_walk_recursive($d['items'], static function ($v, $k) use (&$n) { if ($k === 'url') { $n++; } }); echo $n ? $n . ($n === 1 ? ' page' : ' pages') : 'No access yet'; ?></span></span><span class="chev"><i class="bi bi-chevron-right"></i></span></span>
                    <?= $deskPage ? '</a>' : '</button>' ?>
                    <div class="hd-menu" id="<?= $id ?>" role="menu" aria-label="<?= $esc($d['name']) ?>">
                        <?php if (!$has): ?>
                            <div class="hd-none">No access yet — the Super Admin assigns access to this desk.</div>
                        <?php endif; ?>
                        <?php foreach ($d['items'] as $gi => $it): ?>
                            <?php if (isset($it['items'])): $sid = $id . '-g' . $gi; $openG = $gi === 0; ?>
                                <button type="button" class="hd-grp" aria-expanded="<?= $openG ? 'true' : 'false' ?>" aria-controls="<?= $sid ?>">
                                    <span class="gl"><i class="bi <?= $esc($it['icon'] ?? 'bi-folder') ?>"></i><?= $esc($it['label']) ?></span><i class="bi bi-chevron-right"></i>
                                </button>
                                <div class="hd-sublist" id="<?= $sid ?>" <?= $openG ? '' : 'hidden' ?>>
                                    <?php foreach ($it['items'] as $sub): ?><?= $item($sub) ?><?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <?= $item($it) ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php if (!empty($usd)): // tf-stage2: today's USD rate, entered here by anyone holding the right; read by every costing and payment ?>
    <div class="hd-usd <?= $usd['entered'] ? 'ok' : 'warn' ?>" id="hd_usd" role="dialog" aria-label="Today's USD rate">
        <div class="hd-usd-h" role="heading" aria-level="2"><span class="t"><i class="bi bi-currency-exchange"></i> Today's USD rate · <?= $esc(date('j M Y')) ?></span>
            <?php if ($usd['entered'] && !empty($usd['test'])): ?><span class="st warn" title="The latest entry or correction for today is marked TEST. Enter the real rate as a correction (with the reason) to replace it.">TEST figure — not the real rate</span><?php elseif ($usd['entered']): ?><span class="st ok">Entered</span><?php else: ?><span class="st warn">Not entered today</span><?php endif; ?><button type="button" class="hd-usd-x" id="hd_usd_x" aria-label="Close">&times;</button></div>
        <div class="hd-usd-b">
            <?php if ($usd_may): ?>
            <form class="hd-usd-f" id="hd_usd_f" autocomplete="off">
                <label for="hd_usd_rate"><b>1 USD =</b></label>
                <input type="text" inputmode="decimal" id="hd_usd_rate" value="<?= $usd['entered'] ? $esc(number_format($usd['rate'], 2, '.', '')) : '' ?>" placeholder="<?= $usd['last_rate'] !== null ? $esc(number_format($usd['last_rate'], 2, '.', '')) : '325.00' ?>" aria-label="Rupees for one US dollar">
                <span><b>LKR</b></span>
                <button type="submit" class="hd-usd-save"><?= $usd['entered'] ? 'Correct today\'s rate' : 'Save today\'s rate' ?></button>
            </form>
            <div class="hd-usd-reason" id="hd_usd_rw" hidden><input id="hd_usd_reason" maxlength="255" placeholder="Reason for the correction (required)"></div>
            <?php else: ?>
            <div class="hd-usd-f"><span><b>1 USD =</b></span><span class="hd-usd-ro"><?= $usd['entered'] ? $esc(number_format($usd['rate'], 2)) : '—' ?></span><span><b>LKR</b></span><span class="hd-usd-note">You do not hold the right to enter the rate; ask a colleague who does.</span></div>
            <?php endif; ?>
            <div class="hd-usd-l" id="hd_usd_l">
                <?php if ($usd['entered']): ?>
                    <?php if (!empty($usd['test'])): ?><b style="color:#9a6a12">TEST figure (development) — not the real rate of the day; costings using it are marked for review once the real rate is entered.</b> <?php endif; ?>Entered by <b><?= $esc($usd['by']) ?></b> at <?= $esc(date('H:i', strtotime($usd['at']))) ?> on <?= $esc(date('j M Y', strtotime($usd['at']))) ?><?= $usd['corrections'] ? ' · ' . (int) $usd['corrections'] . ' correction' . ((int) $usd['corrections'] === 1 ? '' : 's') . ' today' : '' ?>
                <?php else: ?>
                    <b><?= $esc($usd['warning']) ?></b><?= $usd['last_by'] ? ' Entered by ' . $esc($usd['last_by']) . '.' : '' ?> Until today's rate is entered, hotel payment requests wait and costings use the last rate with its own date.
                <?php endif; ?>
                <?php if (!empty($usd_history_url)): ?> · <a href="<?= $esc($usd_history_url) ?>">History</a><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($home['links'])): ?>
        <nav class="hd-links" aria-label="More"><span class="lk">
            <?php foreach ($home['links'] as $li => $l): ?><?= $li ? '<i aria-hidden="true"></i>' : '' ?>
                <a href="<?= $esc(base_url($l['url'])) ?>"><i class="bi <?= $esc($l['icon']) ?>"></i><?= $esc($l['label']) ?><i class="bi bi-chevron-right go" aria-hidden="true"></i></a>
            <?php endforeach; ?>
        </span></nav>
    <?php endif; ?>
    <div class="hd-foot">Access is managed by the Super Admin</div>
    </div>
</main>

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script>
// tf-usdpill-r1: the pill opens and closes the rate panel (outside click, Esc and the × close it; #usd opens it)
(function () {
    var pill = document.getElementById('hd_usd_pill'), box = document.getElementById('hd_usd'); if (!pill || !box) return;
    function place() { var r = pill.getBoundingClientRect(), w = box.offsetWidth, x = Math.min(Math.max(12, r.right - w), window.innerWidth - w - 12); box.style.left = x + 'px'; box.style.right = 'auto'; box.style.top = (r.bottom + 8) + 'px'; }
    window.addEventListener('resize', function () { if (box.classList.contains('open')) place(); });
    function set(open) { box.classList.toggle('open', open); if (open) place(); pill.setAttribute('aria-expanded', open ? 'true' : 'false'); if (open) { var r = document.getElementById('hd_usd_rate'); if (r) setTimeout(function () { r.focus(); }, 30); } }
    pill.addEventListener('click', function (e) { e.stopPropagation(); set(!box.classList.contains('open')); });
    document.getElementById('hd_usd_x').addEventListener('click', function () { set(false); pill.focus(); });
    document.addEventListener('click', function (e) { if (box.classList.contains('open') && !box.contains(e.target) && e.target !== pill) set(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && box.classList.contains('open')) { set(false); pill.focus(); } });
    if (location.hash === '#usd') set(true);
})();
</script>
<script>
// tf-stage2: the Home box saves today's rate; a second save for today asks for the reason and is logged as a correction
(function () {
    var f = document.getElementById('hd_usd_f'); if (!f) return;
    var box = document.getElementById('hd_usd'), rate = document.getElementById('hd_usd_rate'), rw = document.getElementById('hd_usd_rw'), reason = document.getElementById('hd_usd_reason'), btn = f.querySelector('.hd-usd-save');
    var entered = box.classList.contains('ok'), msg = null;
    function say(kind, text) { if (!msg) { msg = document.createElement('div'); f.parentNode.insertBefore(msg, document.getElementById('hd_usd_l')); } msg.className = 'hd-usd-msg ' + kind; msg.textContent = text; }
    f.addEventListener('submit', async function (e) {
        e.preventDefault();
        var v = rate.value.trim().replace(/,/g, '');
        if (!v) { say('err', 'Type the rate first.'); rate.focus(); return; }
        if (entered && rw.hidden) { rw.hidden = false; reason.focus(); say('err', 'This corrects the rate already entered today: give the reason, then press the button again.'); return; }
        btn.disabled = true;
        try {
            var r = await fetch('<?= base_url('home/usd_rate/save') ?>', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ rate: v, reason: reason.value }) });
            var j = await r.json();
            if (j.status !== 200) { say('err', j.message || 'Not saved.'); return; }
            say('ok', j.message); setTimeout(function () { location.reload(); }, 1400);
        } catch (err) { say('err', 'The rate could not be saved: ' + err); } finally { btn.disabled = false; }
    });
})();
</script>
<script>
(function () {
    'use strict';
    const desks = document.querySelectorAll('.hd-desk');
    const closeAll = except => desks.forEach(d => { if (d !== except) { d.classList.remove('open'); d.querySelector('.hd-btn').setAttribute('aria-expanded', 'false'); } });
    desks.forEach(d => {
        const b = d.querySelector('.hd-btn');
        if (b.dataset.link) { b.addEventListener('click', () => { if (window.avNavLoading) window.avNavLoading(d.querySelector('.nm').textContent, b.href); }); return; }   // tf-tourdesk-r1
        b.addEventListener('click', e => {
            e.stopPropagation();
            const open = !d.classList.contains('open');
            closeAll(d);
            d.classList.toggle('open', open);
            b.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) { const f = d.querySelector('.hd-menu a, .hd-menu .hd-grp'); if (f && e.detail === 0) f.focus(); }
        });
    });
    document.querySelectorAll('.hd-grp').forEach(g => g.addEventListener('click', e => {
        e.stopPropagation();
        const s = document.getElementById(g.getAttribute('aria-controls'));
        const open = g.getAttribute('aria-expanded') !== 'true';
        g.setAttribute('aria-expanded', open ? 'true' : 'false');
        s.hidden = !open;
    }));
    document.addEventListener('click', e => { if (!e.target.closest('.hd-menu')) closeAll(null); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { const o = document.querySelector('.hd-desk.open .hd-btn'); closeAll(null); if (o) o.focus(); } });

    // the same logout as the shared top bar
    if (typeof window.logout !== 'function') {
        window.logout = function () {
            Swal.fire({ title: 'Are you sure?', text: 'You want to logout from the system?', icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#78b848', cancelButtonColor: '#d33', confirmButtonText: 'Yes, Logout!' }).then(r => {
                if (!r.isConfirmed) return;
                $.ajax({ url: '<?= base_url() ?>logout', method: 'GET', success: function (res) {
                    if (res.status == 200) {
                        localStorage.removeItem('previousNotifications');
                        window.location.href = '<?= session()->get('is_admin') ?>' == 1 ? '<?= base_url() ?>admin' : '<?= base_url() ?>';
                    }
                } });
            });
        };
    }
})();
</script>
</body>
</html>
