<?php
/**
 * tf-beachhome-r1 (18 Nov 2026, Shantha's sunrise beach mock-up): the staff homepage directly on the scenery — logo, live
 * clocks, caption, six desk buttons, Pilot Report and Administration; no side panel, no white cards. The scenery is the
 * preferred photograph of Photo Master → Homepage → "Staff homepage background"; without one the earlier layout shows.
 * Only the look changes: every button, link, the USD rate, search and logout are the same elements as before.
 */
?>
<style id="hd-beach-css">
    html body.hd-beach { background:#f3cf9a url('<?= esc((string) $home_bg) ?>') center center / cover no-repeat fixed !important; min-height:100vh;
        --hd-b1:#842484; --hd-b2:#842484; --hd-db1:#842484; --hd-db2:#842484; --hd-title:#842484; }
    html body.hd-beach .hd-top { position:absolute; left:0; right:0; top:0; background:transparent !important; box-shadow:none !important; border:0 !important; }
    html body.hd-beach .hd-top .hd-user, html body.hd-beach .hd-top .hd-user small { text-shadow:0 1px 3px rgba(0,0,0,.55); }
    .hd-beach .hd-wrap { display:block; max-width:none; margin:0; padding:0; background:none; min-height:100vh; }
    .hd-beach .hd-main { min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:flex-start; order:0;
        padding:clamp(46px,6vh,66px) 16px clamp(16px,3.2vh,38px); }
    .hd-beach .wc.brand { gap:clamp(18px,4vw,70px); margin:0; width:auto; }
    .hd-beach .hd-brand img { height:clamp(84px,14vh,146px); filter:drop-shadow(0 1px 1px rgba(255,255,255,.5)); }
    .hd-beach .wc .wc-c, .hd-beach .wc.brand .wc-c[data-k=jp] { text-align:center; }
    .hd-beach .wc .wc-l, .hd-beach .wc.brand .wc-c[data-k=jp] .wc-l { color:#842484; justify-content:center; font-size:clamp(.78rem,1.05vw,1.05rem); letter-spacing:.06em; }
    .hd-beach .wc .wc-l svg { display:none; }
    .hd-beach .wc .wc-r, .hd-beach .wc.brand .wc-c[data-k=jp] .wc-r { justify-content:center; }
    .hd-beach .wc .wc-t { color:#842484; font-family:Georgia,"Times New Roman",serif; font-weight:700; font-size:clamp(1.6rem,2.7vw,2.6rem); flex-basis:auto; letter-spacing:.02em; }
    .hd-beach .wc .wc-d, .hd-beach .wc .wc-w { display:none !important; }
    .hb-sys { display:flex; align-items:center; gap:clamp(12px,1.6vw,24px); color:#842484; font-size:clamp(.72rem,1.12vw,1.1rem); letter-spacing:.32em; text-transform:uppercase;
        font-weight:500; margin-top:clamp(6px,1.8vh,20px); text-align:center; }
    .hb-sys::before, .hb-sys::after { content:""; flex:none; width:clamp(24px,4.2vw,70px); height:1.5px; background:#842484; opacity:.8; }
    .hb-cap { font-family:Georgia,"Times New Roman",serif; font-style:italic; font-weight:700; color:#842484; text-align:center; letter-spacing:-.01em;
        font-size:clamp(2.2rem,5.3vw,5.5rem); line-height:1.04; margin:clamp(8px,2.6vh,30px) 0 0; text-shadow:0 1px 0 rgba(255,255,255,.35); }
    .hb-swoosh { width:clamp(130px,13vw,220px); height:auto; margin-top:8px; flex:none; }
    .hd-beach .hd-desks { margin:auto 0 0 !important; width:min(1090px,66vw); max-width:none; grid-template-columns:repeat(3,minmax(0,1fr)) !important;
        gap:clamp(12px,2.2vh,22px) clamp(12px,1.2vw,20px); padding-top:18px; }
    .hd-beach .hd-desk[data-desk=tour] { order:1 !important; } .hd-beach .hd-desk[data-desk=reservation] { order:2 !important; } .hd-beach .hd-desk[data-desk=transport] { order:3 !important; }
    .hd-beach .hd-desk[data-desk=masters] { order:4 !important; } .hd-beach .hd-desk[data-desk=finance] { order:5 !important; } .hd-beach .hd-desk[data-desk=approver] { order:6 !important; }
    .hd-beach .hd-desk > .hd-btn { min-height:clamp(60px,8.7vh,84px) !important; border-radius:16px; padding:10px 18px !important; justify-content:center; gap:clamp(10px,1.1vw,18px) !important;
        color:#fff !important; box-shadow:0 6px 18px rgba(60,20,30,.30), inset 0 1px 0 rgba(255,255,255,.30) !important; }
    .hd-beach .hd-desk[data-desk=tour] > .hd-btn, .hd-beach .hd-desk[data-desk=transport] > .hd-btn, .hd-beach .hd-desk[data-desk=finance] > .hd-btn { background:linear-gradient(180deg,#86c153,#69a63b) !important; }
    .hd-beach .hd-desk[data-desk=reservation] > .hd-btn, .hd-beach .hd-desk[data-desk=masters] > .hd-btn, .hd-beach .hd-desk[data-desk=approver] > .hd-btn { background:linear-gradient(180deg,#9b2a9b,#7f237f) !important; }
    .hd-beach .hd-desk > .hd-btn .ic { width:auto !important; height:auto !important; background:none !important; color:#fff !important; border-radius:0; }
    .hd-beach .hd-desk > .hd-btn .ic svg { width:clamp(28px,2.5vw,40px); height:clamp(28px,2.5vw,40px); }
    .hd-beach .hd-desk > .hd-btn .row1 { flex:0 1 auto; margin:0 !important; padding:0 !important; border:0 !important; align-self:center; }
    .hd-beach .hd-desk > .hd-btn .ct, .hd-beach .hd-desk > .hd-btn .chev { display:none !important; }
    .hd-beach .hd-desk > .hd-btn .nm { font-size:clamp(1rem,1.55vw,1.5rem) !important; font-weight:600 !important; text-shadow:0 1px 2px rgba(0,0,0,.22); }
    .hd-beach .hd-links { margin-top:clamp(14px,4.6vh,50px); gap:0; }
    .hd-beach .hd-links::before, .hd-beach .hd-links::after { display:none; }
    .hd-beach .hd-links a { background:none !important; border:0; box-shadow:none !important; color:#842484; font-weight:600; font-size:clamp(.95rem,1.3vw,1.25rem); padding:6px 14px;
        text-shadow:0 1px 0 rgba(255,255,255,.45); }
    .hd-beach .hd-links a:hover { text-decoration:underline; }
    .hd-beach .hd-links a .go { display:none; }
    .hd-beach .hd-links .lk > i { background:#842484; opacity:.5; height:26px; }
    .hd-beach .hd-foot { display:none; }
    @media (max-width:1100px) { .hd-beach .hd-desks { width:min(1090px,92vw); } }
    @media (max-width:760px) { .hd-beach .hd-desks { grid-template-columns:repeat(2,minmax(0,1fr)) !important; } .hd-beach .hd-links { flex-direction:row; } .hd-beach .hd-links .lk { flex-direction:row; width:auto; } .hd-beach .hd-links a { width:auto; }
        .hd-beach .hd-links .lk > i { display:block; } html body.hd-beach { background-attachment:scroll !important; } }
    /* phones: logo on top, the two clocks side by side, the six desks two across so the whole page fits one screen */
    @media (max-width:520px) {
        .hd-beach .hd-main { padding:54px 12px 18px; }
        .hd-beach .wc.brand { grid-template-columns:1fr 1fr !important; gap:4px 10px; width:100%; }
        .hd-beach .wc.brand .wc-mid { grid-column:1 / -1; order:-1; text-align:center; }
        .hd-beach .hd-brand img { height:72px; }
        .hd-beach .wc .wc-t { font-size:1.55rem; }
        .hb-sys { letter-spacing:.2em; font-size:.66rem; margin-top:10px; } .hb-sys::before, .hb-sys::after { width:18px; }
        .hb-cap { font-size:2.05rem; margin-top:12px; }
        .hd-beach .hd-desks { grid-template-columns:repeat(2,minmax(0,1fr)) !important; width:100%; gap:10px; }
        .hd-beach .hd-desk > .hd-btn { flex-direction:column; gap:4px !important; min-height:74px !important; padding:8px 6px !important; }
        .hd-beach .hd-desk > .hd-btn .ic svg { width:26px; height:26px; }
        .hd-beach .hd-desk > .hd-btn .nm { font-size:.95rem !important; }
        .hd-beach .hd-links { margin-top:18px; } .hd-beach .hd-links a { font-size:.9rem; padding:6px 8px; gap:7px; }
    }
</style>
