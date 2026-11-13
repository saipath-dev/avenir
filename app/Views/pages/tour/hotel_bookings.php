<?= view('layouts/head') ?>
<style>
    /* ---------- Hotel Bookings (same tokens as the Tour Inquiry panel; the Tour Desk bar re-points them) ---------- */
    .tf { --tf-navy:#1e3553; --tf-navy-2:#2c4a73; --tf-teal:#0f8b7e; --tf-teal-soft:#e3f4f1; --tf-amber:#1A1A1A; --tf-amber-soft:#FFFFFF;
          --tf-red:#b23a32; --tf-red-soft:#fbe9e7; --tf-green:#1f8a4c; --tf-green-soft:#e4f4ea; --tf-blue-soft:#e6eef8;
          --tf-ink:#1d2733; --tf-muted:#5f6f80; --tf-line:#dfe5ec; --tf-bg:#f4f6f9; --tf-card:#fff; --tf-radius:10px; color:var(--tf-ink); }
    .tf .tf-card { background:var(--tf-card); border:1px solid var(--tf-line); border-radius:var(--tf-radius); box-shadow:0 1px 2px rgba(16,24,40,.04); }
    .tf .tf-card-h { padding:11px 14px; border-bottom:1px solid var(--tf-line); display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    .tf .tf-card-h h6 { margin:0; font-weight:600; font-size:13.5px; }
    .tf .tf-card-b { padding:14px; }
    .tf .tf-title { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px; }
    .tf .tf-title h4 { margin:0; font-weight:600; color:var(--tf-navy); }
    .tf .tf-title .sub { color:var(--tf-muted); font-size:12.5px; max-width:820px; }
    .tf .help { font-size:12px; color:var(--tf-muted); }
    .tf .btn-primary { background:var(--tf-navy); border-color:var(--tf-navy); }
    .tf .btn-light { background:#fff; border:1px solid var(--tf-line); color:var(--tf-ink); }
    .tf .btn-light:hover { background:var(--tf-bg); }
    .tf .btn-success { background:var(--tf-green); border-color:var(--tf-green); }
    .tf .not-installed { padding:14px 16px; border-radius:8px; background:var(--tf-amber-soft); color:var(--tf-amber); font-size:13px; }
    .tf .tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
    .tf .tbl th { text-align:left; font-weight:600; color:var(--tf-muted); font-size:11px; letter-spacing:.04em; text-transform:uppercase; padding:8px 10px; border-bottom:1px solid var(--tf-line); white-space:nowrap; }
    .tf .tbl td { padding:9px 10px; border-bottom:1px solid var(--tf-line); vertical-align:top; }
    .tf .tbl tr:last-child td { border-bottom:0; }
    .tf .tbl tr.rowlink { cursor:pointer; }
    .tf .tbl tr.rowlink:hover td { background:#fafbfd; }
    .tf .mono { font-family:ui-monospace, SFMono-Regular, Menlo, monospace; font-variant-numeric:tabular-nums; }
    .tf .pill { display:inline-block; font-size:10.5px; font-weight:600; border-radius:20px; padding:2px 8px; line-height:1.6; white-space:nowrap; }
    .tf .pill-green { background:var(--tf-green-soft); color:var(--tf-green); }
    .tf .pill-grey { background:#eef1f5; color:var(--tf-muted); }
    .tf .pill-blue { background:var(--tf-blue-soft); color:var(--tf-navy); }
    .tf .pill-amber { background:var(--tf-amber-soft); color:var(--tf-amber); }
    .tf .pill-red { background:var(--tf-red-soft); color:var(--tf-red); }
    .tf .pill-teal { background:var(--tf-teal-soft); color:var(--tf-teal); }
    .tf .flag { display:block; font-size:11.5px; color:var(--tf-amber); font-weight:600; margin-top:2px; }
    .tf .flag.bad { color:var(--tf-red); }
    .tf .chips { display:flex; gap:6px; flex-wrap:wrap; }
    .tf .chip { border:1px solid var(--tf-line); background:#fff; border-radius:9px; padding:5px 11px; font-size:12px; font-weight:500; cursor:pointer; color:var(--tf-ink); }
    .tf .chip .n { font-family:ui-monospace, Menlo, monospace; font-size:11px; color:var(--tf-muted); margin-left:4px; }
    .tf .chip.on { background:var(--tf-blue-soft); border-color:transparent; color:var(--tf-navy); font-weight:600; }
    .tf .chip.on .n { color:var(--tf-navy); }
    .tf .empty { color:var(--tf-muted); font-size:12.5px; padding:14px 0; text-align:center; }
    .tf .kv { display:grid; grid-template-columns:130px 1fr; gap:5px 12px; font-size:12.5px; }
    .tf .kv dt { color:var(--tf-muted); font-weight:500; }
    .tf .kv dd { margin:0; }
    .tf .box { border:1px solid var(--tf-line); border-radius:8px; padding:11px 13px; margin-bottom:12px; }
    .tf .box h6 { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--tf-muted); font-weight:700; margin:0 0 8px; }
    .tf .trail { font-size:12px; }
    .tf .trail div { padding:5px 0; border-bottom:1px dashed var(--tf-line); }
    .tf .trail div:last-child { border-bottom:0; }
    .tf .trail .who { color:var(--tf-muted); }
    .tf .acts { display:flex; gap:6px; flex-wrap:wrap; }
    .tf .amended { border-left:3px solid var(--tf-amber); }
    /* the tour opened: its header, then its hotels and its activity lines */
    .tf .tv-head { display:flex; gap:12px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; padding:12px 14px; }
    .tf .tv-head .who { font-size:14px; font-weight:600; }
    .tf .tv-head .who small { font-weight:400; color:var(--tf-muted); font-size:12px; }
    .tf .tv-mark { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .tf .prog { display:inline-flex; align-items:center; gap:6px; font-size:12px; white-space:nowrap; }
    .tf .prog .bar { width:64px; height:6px; border-radius:3px; background:#e4e8ee; overflow:hidden; display:inline-block; }
    .tf .prog .bar i { display:block; height:100%; background:var(--tf-green); }
    .tf .prog.problem .bar i { background:var(--tf-red); }
    .tf .msg { padding:6px 0; border-bottom:1px dashed var(--tf-line); font-size:12px; }
    .tf .msg:last-child { border-bottom:0; }
    .tf .msg .k { display:inline-block; min-width:64px; font-weight:600; }
    .tf .msg .k.reply { color:var(--tf-teal); }
    .tf .msg .k.failed { color:var(--tf-red); }
    .modal .modal-content { border-radius:12px; }
    /* tf-resdesk-r2: the Reservation Desk — new bookings, each hotel's stage, its documents (Avenir purple and green, dark text) */
    .tf { --rd-purple:#7B2382; --rd-purple-d:#5A1A5D; --rd-purple-soft:#F3E8F5; --rd-green:#2F6E16; --rd-green-soft:#EAF6E2; --rd-red:#8C1D18; --rd-red-soft:#FBE9E7; }
    .tf .tbl tr.rd-new td { background:var(--rd-green-soft); }
    .tf .tbl tr.rd-new td:first-child { box-shadow:inset 4px 0 0 #4E9A2A; }
    .tf .tbl tr.rd-new:hover td { background:#DDF0D0; }
    .tf .rd-newtag { display:inline-block; margin-top:3px; background:#2F6E16; color:#fff; border-radius:5px; padding:1px 7px; font-size:10.5px; font-weight:700; letter-spacing:.04em; }
    .tf .rd-prog { display:flex; gap:3px; margin-bottom:3px; } .tf .rd-prog i { flex:1; height:7px; border-radius:4px; background:#D5CCDA; min-width:10px; }
    .tf .rd-prog i.c { background:#2F6E16; } .tf .rd-prog i.f { background:#B3261E; } .tf .rd-prog i.p { background:#7B2382; }
    .tf .rd-stays { display:flex; flex-direction:column; }
    .tf .rd-stay { padding:14px 16px; border-top:1px solid var(--tf-line); }
    .tf .rd-stay:first-child { border-top:0; }
    .tf .rd-stay h5 { margin:0; font-size:15.5px; font-weight:700; color:#1F1A24; }
    .tf .rd-sub { color:#3B3341; font-size:13px; margin-top:2px; }
    .tf .rd-stage { display:inline-block; border-radius:16px; padding:4px 12px; font-size:12.5px; font-weight:700; margin:8px 0 6px; }
    .tf .rd-t-blue { background:#E6EEF8; color:#1E3553; } .tf .rd-t-purple { background:var(--rd-purple-soft); color:var(--rd-purple-d); }
    .tf .rd-t-teal { background:#E3F4F1; color:#0B5F56; } .tf .rd-t-green { background:var(--rd-green-soft); color:var(--rd-green); }
    .tf .rd-t-red { background:var(--rd-red-soft); color:var(--rd-red); border:1px solid #F0B8B0; } .tf .rd-t-amber { background:#FFFFFF; color:#1A1A1A; border:1px solid #D9D3DD; } .tf .rd-t-grey { background:#EEF1F5; color:#4A5562; }
    .tf .rd-facts { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:4px 18px; font-size:13px; color:#1F1A24; margin:4px 0 10px; }
    .tf .rd-facts b { color:#4A1450; font-weight:600; }
    .tf .rd-btns { display:flex; gap:8px; flex-wrap:wrap; }
    .tf .rd-btn { border:1px solid #CDBFD3; background:#fff; color:#3B1440; border-radius:9px; padding:7px 13px; font-size:13px; font-weight:600; text-decoration:none; cursor:pointer; }
    .tf .rd-btn:hover { background:var(--rd-purple-soft); color:#3B1440; }
    .tf .rd-btn.primary { background:var(--rd-purple); border-color:var(--rd-purple); color:#fff; } .tf .rd-btn.primary:hover { background:var(--rd-purple-d); color:#fff; }
    .tf .rd-btn.green { background:#2F6E16; border-color:#2F6E16; color:#fff; }
    .tf .rd-btn[disabled] { opacity:.55; cursor:not-allowed; }
    .tf .rd-err { color:var(--rd-red); font-size:12.5px; font-weight:600; margin:-4px 0 8px; }
    .tf .rd-dept { background:#F4F1F6; border-radius:8px; padding:9px 13px; font-size:13px; color:#2B2430; margin:0 14px 10px; }
    .tf .cancel-flag { background:#fbe9e6; color:#8a2a22; border:1px solid #f0b8b0; border-radius:8px; padding:8px 12px; margin:8px 0; font-size:13px; }

    /* ================= tf-resdesk-r2 DESIGN (12 Nov 2026, Shantha's mock-up): Avenir purple #842484, green #78b848, dark text ================= */
    .tf { --av-p:#842484; --av-p-d:#5E1660; --av-p-soft:#F5EAF5; --av-g:#78b848; --av-g-d:#5E9A33; --av-g-ink:#10300A; --av-ink:#1B1620; --av-ink2:#3A3340; --av-line:#E4DAE6; color:var(--av-ink); }
    .tf .rd2-hero { background:linear-gradient(0deg,#842484,#842484); color:#fff; border-radius:16px; padding:22px 26px; display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:12px; box-shadow:0 2px 6px rgba(80,20,80,.18); }
    .tf .rd2-kicker { font-size:12px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#F6DCF6; }
    .tf .rd2-h1 { margin:2px 0 4px; font-size:26px; font-weight:800; color:#fff; letter-spacing:-.01em; }
    .tf .rd2-sub { font-size:14px; color:#FBEFFB; max-width:760px; }
    .tf .rd2-hero-r { display:flex; gap:8px; flex-wrap:wrap; }
    .tf .rd2-hbtn { background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.45); color:#fff; border-radius:10px; padding:8px 14px; font-size:13.5px; font-weight:600; text-decoration:none; }
    .tf .rd2-hbtn:hover { background:rgba(255,255,255,.26); color:#fff; }
    .tf .rd2-steps { display:flex; gap:10px; flex-wrap:wrap; margin:0 0 16px; }
    .tf .rd2-step { display:inline-flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--av-line); color:var(--av-ink2); border-radius:12px; padding:10px 18px; font-size:14px; font-weight:600; text-decoration:none; }
    .tf .rd2-step span { width:22px; height:22px; border-radius:50%; background:#EFE6F0; color:var(--av-p); display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; }
    .tf .rd2-step.on { background:var(--av-p); border-color:var(--av-p); color:#fff; } .tf .rd2-step.on span { background:#fff; color:var(--av-p); }
    .tf .rd2-step:hover:not(.on) { border-color:var(--av-p); color:var(--av-p); }
    .tf .rd2-card { border:1px solid var(--av-line); border-radius:16px; overflow:hidden; box-shadow:0 1px 3px rgba(40,10,40,.06); }
    .tf .rd2-card > .tf-card-h, .tf #tour_view .tf-card > .tf-card-h { background:#FBF7FB; border-bottom:1px solid var(--av-line); padding:12px 18px; }
    .tf #tour_view .tf-card > .tf-card-h h6 { font-size:16px; font-weight:800; color:var(--av-p-d); }
    .tf .chip { border-radius:20px; padding:6px 14px; font-size:13px; border-color:var(--av-line); color:var(--av-ink2); }
    .tf .chip.on { background:var(--av-p); color:#fff; } .tf .chip.on .n { color:#F6DCF6; }
    .tf .rd2-card .tbl { font-size:13.5px; }
    .tf .rd2-card .tbl thead th { background:var(--av-p); color:#fff; font-size:11.5px; letter-spacing:.06em; padding:11px 12px; border:0; }
    .tf .rd2-card .tbl td { padding:13px 12px; border-bottom:1px solid #EFE7F0; vertical-align:middle; color:var(--av-ink); }
    .tf .rd2-card .tbl td.num, .tf .rd2-card .tbl th.num { text-align:center; }
    .tf .rd2-card .tbl tr.rowlink:hover td { background:#FAF5FA; }
    .tf .rd2-card .tbl tr.rd-new td { background:#EEF7E6; } .tf .rd2-card .tbl tr.rd-new:hover td { background:#E3F1D6; }
    .tf .rd2-card .tbl tr.rd-new td:first-child { box-shadow:inset 5px 0 0 var(--av-g); }
    .tf .rd2-tno { font-weight:800; font-size:14px; color:var(--av-ink); white-space:nowrap; }
    .tf .rd2-grp { font-weight:600; } .tf .rd2-muted { color:#5B5361; font-size:12px; margin-top:2px; }
    .tf .rd2-date { white-space:nowrap; font-variant-numeric:tabular-nums; }
    .tf .rd-newtag { background:var(--av-g); color:var(--av-g-ink); border-radius:6px; padding:2px 8px; font-size:11px; font-weight:800; letter-spacing:.04em; text-transform:uppercase; margin-top:4px; display:inline-block; }
    .tf .rd2-btn { display:inline-flex; align-items:center; gap:6px; border:1px solid #CBBBD0; background:#fff; color:#2E1532; border-radius:10px; padding:8px 15px; font-size:13.5px; font-weight:700; text-decoration:none; cursor:pointer; line-height:1.2; white-space:nowrap; }
    .tf .rd2-btn:hover { border-color:var(--av-p); color:var(--av-p); background:#FCF8FC; }
    .tf .rd2-btn.go { background:var(--av-g); border-color:var(--av-g); color:var(--av-g-ink); }
    .tf .rd2-btn.go:hover { background:var(--av-g-d); border-color:var(--av-g-d); color:#fff; }
    .tf .rd2-btn.ghost { background:transparent; }
    .tf .rd2-btn[disabled] { opacity:.5; cursor:not-allowed; }
    .tf .rd2-act { text-align:right; white-space:nowrap; }
    .tf .rd2-tvhead { display:block !important; padding:18px 22px !important; }
    .tf .rd2-tv-top { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
    .tf .rd2-tv-tools { display:flex; gap:8px; flex-wrap:wrap; }
    .tf .rd2-h2 { margin:0; font-size:24px; font-weight:800; color:var(--av-ink); } .tf .rd2-h2 span { color:var(--av-p); font-weight:700; }
    .tf .rd2-tv-sub { color:var(--av-ink2); font-size:14px; margin-top:3px; }
    .tf .rd2-facts3 { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; margin-top:14px; }
    .tf .rd2-facts3 > div { background:#FAF6FA; border:1px solid #EFE5F0; border-radius:10px; padding:9px 12px; }
    .tf .rd2-facts3 label { display:block; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#6A5F70; margin:0 0 2px; }
    .tf .rd2-facts3 b { font-size:14.5px; color:var(--av-ink); }
    .tf .rd2-tv-mark:empty { display:none; } .tf .rd2-tv-mark { margin-top:12px; }
    .tf .rd2-strip { margin-top:12px; } .tf .rd2-strip summary, .tf #tv_details_wrap summary { cursor:pointer; font-size:12px !important; font-weight:700; color:var(--av-p) !important; letter-spacing:.04em; }
    .tf .rd-dept { margin:14px 18px 4px; background:var(--av-p-soft); border:1px solid #EAD7EB; color:#2E1532; border-radius:10px; padding:11px 14px; font-size:13.5px; }
    .tf .rd-stays { padding:6px 0 4px; }
    .tf .rd-stay { margin:12px 18px; border:1px solid var(--av-line); border-radius:14px; padding:16px 18px; background:#fff; border-top:1px solid var(--av-line) !important; }
    .tf .rd-stay h5 { font-size:17px; font-weight:800; color:var(--av-ink); }
    .tf .rd-sub { color:var(--av-ink2); font-size:13.5px; margin-top:3px; }
    .tf .rd-stage { font-size:13px; padding:5px 13px; margin:10px 0 12px; }
    .tf .rd-facts { display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:10px 22px; margin:0 0 14px; padding:12px 14px; background:#FBF8FB; border-radius:10px; border:1px solid #F0E7F1; }
    .tf .rd-facts .f label { display:block; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:#6A5F70; margin:0 0 2px; }
    .tf .rd-facts .f span { font-size:13.5px; color:var(--av-ink); }
    .tf .rd-btns { gap:8px; }
    .tf .rd-err { margin:0 0 12px; }
    .tf .rd2-card .tf-card-h .chips { gap:8px; }
    .tf .form-control-sm, .tf .form-select-sm { border-radius:9px; border-color:#D9CDDD; }
    .tf .td-dots { border-radius:9px; }
    .tf #acts_card .tbl thead th, .tf #sreq_card .tbl thead th { background:#F5EDF6; color:#4A1450; }
    .tf .rd2-card .tbl td.mono { white-space:nowrap; }
    .tf .rd2-card .pill { font-size:12px; padding:4px 11px; }
    .tf .rd2-card .pill-blue { background:#F5EAF5; color:#5E1660; }
    .tf .rd2-card .pill-amber { background:#FFFFFF; color:#1A1A1A; border:1px solid #D9D3DD; }

    /* ================= tf-resdesk-r2 DESIGN v2 — the mock-up (12 Nov 2026). Everything under .rd3; nothing from .tf reaches it. ================= */
    .rd3 { --p:#842484; --p-d:#6A1C6A; --ink:#111111; --ink2:#55505A; --line:#E3E0E6; --soft:#F2F0F5; --ok:#3B7A1F; --bad:#A3261E;
           font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Inter, Roboto, "Helvetica Neue", Arial, sans-serif; color:var(--ink); font-size:16px; line-height:1.5; }
    .rd3 *, .rd3 *::before, .rd3 *::after { box-sizing:border-box; }
    .rd3-shell { max-width:860px; margin:4px auto 32px; background:#fff; border-radius:16px; box-shadow:0 1px 3px rgba(0,0,0,.08); overflow:hidden; }
    .rd3-hero { background:var(--p); padding:22px 22px 26px; }
    .rd3-brand { font-size:20px; font-weight:800; color:#1A0E1B; line-height:1.25; }
    .rd3-desk { font-size:15px; color:#FFFFFF; margin-top:14px; }
    .rd3-nav { display:flex; flex-wrap:wrap; gap:8px; padding:14px 22px 16px; border-bottom:1px solid var(--line); }
    .rd3 .rd3-navb { display:inline-flex; align-items:center; min-height:38px; padding:8px 13px; border:1px solid #D6D2DA; border-radius:8px; background:#fff; color:var(--ink); font-size:15px; font-weight:500; text-decoration:none; }
    .rd3 .rd3-navb.on { background:var(--p); border-color:var(--p); color:#fff; }
    .rd3 .rd3-navb:hover:not(.on) { border-color:var(--p); color:var(--p); }
    .rd3-main { padding:18px 14px 24px; }
    .rd3 .rd3-h1 { font-size:26px; font-weight:800; line-height:1.25; margin:0 0 8px; color:var(--ink); letter-spacing:-.01em; }
    .rd3 .rd3-summary { font-size:15px; color:var(--ink2); margin:0 0 12px; }
    .rd3 .rd3-b { display:inline-flex; align-items:center; justify-content:center; min-height:38px; padding:8px 13px; border:1px solid #D6D2DA; border-radius:8px; background:#fff; color:var(--ink); font-size:15px; font-weight:500; text-decoration:none; cursor:pointer; line-height:1.2; }
    .rd3 .rd3-b:hover { border-color:var(--p); color:var(--p); }
    .rd3 .rd3-b.primary { background:var(--p); border-color:var(--p); color:#fff; }
    .rd3 .rd3-b.primary:hover { background:var(--p-d); color:#fff; }
    .rd3 .rd3-b[disabled] { opacity:.45; cursor:not-allowed; }
    .rd3-btns { display:flex; flex-wrap:wrap; gap:7px; margin-top:10px; }
    .rd3-dept { background:var(--soft); color:var(--ink); font-size:15px; padding:14px 14px; margin:16px 0 8px; }
    .rd3-hotels { margin-top:6px; }
    .rd3-hotel { padding:18px 0 20px; border-bottom:1px solid var(--line); }
    .rd3-hotel-top { display:flex; align-items:flex-start; gap:10px; }
    .rd3 .rd3-hname { flex:1; font-size:17px; font-weight:700; margin:0; line-height:1.3; color:var(--ink); }
    .rd3 .rd3-hline { font-size:14.5px; color:var(--ink2); margin:5px 0 12px; }
    .rd3-status { display:inline-block; background:var(--soft); color:var(--ink); font-size:14.5px; border-radius:20px; padding:4px 12px; margin-bottom:12px; }
    .rd3-status.warn { background:#FFFFFF; color:#1A1A1A; border:1px solid #D9D3DD; } .rd3-status.bad { background:#FBE7E5; color:var(--bad); font-weight:600; }
    .rd3-paid { flex:none; background:var(--soft); color:var(--ok); font-size:14.5px; border-radius:22px; padding:7px 12px; }
    .rd3-dates { display:grid; grid-template-columns:minmax(0,max-content) minmax(0,max-content); column-gap:20px; row-gap:18px; font-size:14px; letter-spacing:-.005em; color:var(--ink); margin:2px 0 8px; }
    @media (max-width: 600px) { .rd3 { margin-left:-12px; margin-right:-12px; } .rd3-shell { margin-top:0; } .rd3-dates { font-size:13.5px; column-gap:16px; } .rd3-dates span { white-space:nowrap; } }
    .rd3 .rd3-bad { color:var(--bad); font-size:14px; font-weight:600; margin:4px 0; }
    .rd3-warn { color:#6A4208; font-size:13.5px; margin:-4px 0 10px; }
    .rd3-fold { margin-top:12px; }
    .rd3-fold > summary { cursor:pointer; color:var(--p); font-size:14.5px; font-weight:600; list-style:revert; }
    .rd3-more { margin-top:22px; padding-top:14px; border-top:1px solid var(--line); }
    .rd3-more > summary { font-size:16px; }
    .rd3-dl { display:grid; grid-template-columns:1fr; gap:10px; margin:12px 0 4px; padding:14px; background:#FAF9FB; border:1px solid var(--line); border-radius:10px; }
    .rd3-dl > div { display:grid; grid-template-columns:1fr; gap:1px; min-width:0; }
    .rd3-dl dd { overflow-wrap:anywhere; min-width:0; }
    .rd3-dl dt { font-weight:600; color:var(--ink2); font-size:14px; } .rd3-dl dd { margin:0; font-size:14.5px; color:var(--ink); }
    .rd3-dl a { color:var(--p); }
    .rd3-chips { display:flex; gap:8px; flex-wrap:wrap; margin:4px 0 6px; }
    .rd3 .chip { border:1px solid #D6D2DA; background:#fff; border-radius:8px; padding:7px 12px; font-size:14.5px; color:var(--ink); cursor:pointer; }
    .rd3 .chip .n { font-size:13px; color:var(--ink2); margin-left:5px; }
    .rd3 .chip.on { background:var(--p); border-color:var(--p); color:#fff; } .rd3 .chip.on .n { color:#F2DDF2; }
    .rd3-filterrow { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; align-items:flex-end; }
    .rd3-lab { display:flex; flex-direction:column; gap:4px; font-size:13.5px; color:var(--ink2); }
    .rd3-lab input, .rd3-lab select { min-height:38px; border:1px solid #D6D2DA; border-radius:8px; padding:6px 10px; font-size:15px; color:var(--ink); background:#fff; }
    .rd3-links { margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; }
    .rd3-tours { margin-top:12px; }
    .rd3-tour { padding:16px 14px 18px; border-bottom:1px solid var(--line); cursor:pointer; border-radius:0; }
    .rd3-tour:hover { background:#FCFAFC; }
    .rd3-tour.is-new { background:#F6ECF6; border-left:5px solid var(--p); }
    .rd3-tour-top { display:flex; align-items:center; gap:10px; }
    .rd3 .rd3-tour-no { flex:1; font-size:18px; font-weight:800; margin:0; color:var(--ink); }
    .rd3-new { background:var(--p); color:#fff; font-size:13px; font-weight:700; border-radius:20px; padding:3px 11px; }
    .rd3 .rd3-sub { font-size:14.5px; color:var(--ink2); margin-top:3px; }
    .rd3-tour-prog { font-size:14.5px; color:var(--ink); margin-top:8px; }
    .rd3-empty { color:var(--ink2); font-size:15px; padding:18px 0; }
    .rd3-strip { margin-top:12px; }
    .rd3 #acts_card, .rd3 #sreq_card { margin-top:14px; }
    @media (min-width: 900px) {
        .rd3-hero { padding:26px 32px 30px; } .rd3-nav { padding:16px 32px 18px; } .rd3-main { padding:24px 32px 32px; }
        .rd3 .rd3-h1 { font-size:30px; } .rd3-dept { padding:15px 18px; }
        .rd3-dl { grid-template-columns:1fr 1fr; column-gap:24px; }
        .rd3-dl > div { grid-template-columns:1fr; gap:2px; }
    }


    /* ================= tf-resdesk-r2 (13 Nov 2026): Pending Reservations / Confirmed Hotel Reservations — Shantha's two mock-ups ================= */
    .rd3:not(.in-tour) .rd3-hero, .rd3:not(.in-tour) .rd3-nav { display:none; }
    .rd3:not(.in-tour) .rd3-shell { max-width:1240px; border-radius:30px; box-shadow:0 2px 14px rgba(40,20,50,.08); background:#F8F7FA; }
    .rd3:not(.in-tour) .rd3-main { padding:36px 36px 30px; }
    .rdl { font-family:"Segoe UI", "Noto Sans", Inter, Arial, sans-serif; color:#111; }
    .rd3 .rdl-h1 { font-size:44px; font-weight:700; color:#111; margin:0 0 34px; padding-left:28px; position:relative; line-height:1.1; border:0; letter-spacing:-.01em; }
    .rd3 .rdl-h1::before { content:""; position:absolute; left:0; top:2px; bottom:2px; width:8px; border-radius:4px; background:#78B848; }
    .rdl-tabs { display:flex; gap:16px; flex-wrap:wrap; padding-bottom:14px; border-bottom:4px solid #78B848; margin-bottom:20px; }
    .rd3 .rdl-tab { width:354px; max-width:100%; height:62px; border:1.5px solid #C9C4CD; border-radius:6px; background:#fff; color:#111; font-size:24px; font-weight:700; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,.06); }
    .rd3 .rdl-tab[data-desk="other"] { width:334px; }
    .rd3 .rdl-tab.on { background:#842484; border-color:#842484; color:#fff; }
    .rdl-sub { display:flex; gap:14px; flex-wrap:wrap; margin:0 0 20px; }
    .rd3 .rdl-subb { height:44px; padding:0 34px; border:1.5px solid #C9C4CD; border-radius:6px; background:#fff; color:#111; font-size:21px; font-weight:500; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,.05); }
    .rd3 .rdl-subb.on { background:#78B848; border-color:#78B848; color:#fff; font-weight:600; }
    .rd3 .rdl-subb.on[data-list="pending"] { background:#842484; border-color:#842484; }
    .rdl-search { display:flex; gap:16px; flex-wrap:wrap; align-items:center; margin:0 0 24px; }
    .rd3 .rdl-q { flex:0 1 515px; display:flex; align-items:center; gap:14px; height:48px; border:1.5px solid #C9C4CD; border-radius:6px; padding:0 18px; background:#fff; margin:0; }
    .rd3 .rdl-q input { border:0; outline:0; flex:1; min-width:0; font-size:21px; color:#111; background:transparent; }
    .rd3 .rdl-q input::placeholder { color:#5E5964; }
    .rd3 .rdl-date { position:relative; display:inline-flex; align-items:center; gap:12px; height:48px; width:220px; padding:0 16px; border:1.5px solid #C9C4CD; border-radius:6px; background:#fff; margin:0; cursor:pointer; font-size:20px; color:#222; }
    .rd3 .rdl-date .rdl-dtxt { flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .rd3 .rdl-date input { position:absolute; inset:0; opacity:0; width:100%; height:100%; cursor:pointer; border:0; }
    .rd3 .rdl-go { height:44px; width:134px; border:0; border-radius:6px; background:#78B848; color:#fff; font-size:22px; font-weight:700; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,.12); margin-left:auto; }
    .rd3 .rdl-go:hover { background:#6AA53D; }
    .rdl-table { border:1.5px solid #D9D6DC; border-radius:8px; overflow:hidden; background:#fff; }
    .rdl-head, .rdl-row { display:grid; grid-template-columns:236px 122px 386px minmax(200px,1fr) 150px; align-items:center; padding:0 18px 0 40px; }
    .rdl-head { background:#ECEAEF; height:58px; font-size:24px; font-weight:700; color:#111; }
    .rdl-row { height:62px; border-top:1px solid #E8E5EA; font-size:26px; color:#111; }
    .rdl-row > span:nth-child(2) { padding-left:18px; }
    .rdl-client { font-weight:700; }
    .rdl-oc { text-align:right; }
    .rd3 .rdl-open { display:inline-flex; align-items:center; gap:8px; height:36px; padding:0 20px; border:0; border-radius:6px; background:#78B848; color:#fff; font-size:19px; font-weight:700; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,.18); white-space:nowrap; }
    .rd3 .rdl-open:hover { background:#6AA53D; }
    .rdl-light { display:inline-flex; flex-direction:column; gap:3px; background:#3A383D; border-radius:8px; padding:4px 5px; vertical-align:middle; box-shadow:inset 0 0 0 1px #1E1D20; }
    .rdl-light i { width:12px; height:12px; border-radius:50%; background:#77737B; display:block; }
    .rdl-light i.on-r { background:#FF3B30; box-shadow:0 0 7px 2px rgba(255,60,50,.75); }
    .rdl-light i.on-y { background:#FFE21A; box-shadow:0 0 7px 2px rgba(255,226,26,.75); }
    .rdl-light i.on-g { background:#47DE36; box-shadow:0 0 7px 2px rgba(80,230,60,.75); }
    .rdl-empty { padding:24px 40px; font-size:22px; color:#222; border-top:1px solid #E8E5EA; }
    .rdl-legend { display:flex; gap:34px; flex-wrap:wrap; margin:22px 0 0; font-size:21px; color:#111; }
    .rdl-legend .dot { display:inline-block; width:22px; height:22px; border-radius:50%; vertical-align:-4px; margin-right:10px; }
    .rdl-legend .dot.red { background:#E3242B; } .rdl-legend .dot.yellow { background:#FFE03A; } .rdl-legend .dot.green { background:#78B848; }
    .rdl-legend b { font-weight:700; }
    .rdl-pager { display:flex; align-items:center; gap:22px; flex-wrap:wrap; margin-top:22px; font-size:20px; color:#111; }
    .rdl-rpp { display:inline-flex; align-items:center; gap:16px; margin:0; font-weight:400; }
    .rdl-sel { position:relative; display:inline-flex; align-items:center; }
    .rd3 .rdl-sel select { appearance:none; -webkit-appearance:none; height:38px; width:76px; padding:0 30px 0 14px; border:1.5px solid #C9C4CD; border-radius:6px; background:#fff; font-size:20px; font-weight:700; color:#111; }
    .rdl-sel .rdl-chev { position:absolute; right:10px; pointer-events:none; }
    .rdl-sep { width:1.5px; height:34px; background:#CFCAD3; }
    .rdl-pbtns { margin-left:auto; display:flex; gap:18px; }
    .rd3 .rdl-pbtns button { height:38px; padding:0 22px; border:1.5px solid #C9C4CD; border-radius:6px; background:#fff; font-size:19px; font-weight:700; color:#111; cursor:pointer; }
    .rd3 .rdl-pbtns button b { font-size:22px; line-height:1; }
    .rd3 .rdl-pbtns button[disabled] { color:#111; opacity:1; cursor:default; }
    @media (max-width: 1100px) {
        .rdl-head, .rdl-row { grid-template-columns:minmax(120px,1.2fr) 80px minmax(140px,1.6fr) minmax(140px,1.6fr) 120px; padding:0 14px 0 20px; }
        .rdl-head { font-size:18px; } .rdl-row { font-size:19px; } .rdl-row > span:nth-child(2) { padding-left:8px; }
        .rd3 .rdl-go { margin-left:0; }
    }
    @media (max-width: 760px) {
        .rd3:not(.in-tour) .rd3-main { padding:22px 16px; }
        .rd3 .rdl-h1 { font-size:32px; padding-left:22px; } .rd3 .rdl-tab { width:auto; flex:1; font-size:18px; height:52px; }
        .rd3 .rdl-subb { font-size:16px; padding:0 14px; flex:1; }
        .rd3 .rdl-q, .rd3 .rdl-date { flex:1 1 100%; width:auto; }
        .rdl-head { display:none; }
        .rdl-row { grid-template-columns:1fr auto; grid-template-areas:"inq light" "agent agent" "client open"; height:auto; row-gap:6px; padding:14px 16px; font-size:18px; }
        .rdl-row > span:nth-child(1) { grid-area:inq; } .rdl-row > span:nth-child(2) { grid-area:light; padding:0; } .rdl-row > span:nth-child(3) { grid-area:agent; }
        .rdl-row > span:nth-child(4) { grid-area:client; } .rdl-row > span:nth-child(5) { grid-area:open; }
        .rdl-legend { font-size:16px; gap:16px; } .rdl-pager { font-size:16px; } .rdl-pbtns { margin-left:0; }
    }
</style>
<body>
<div id="layout-wrapper">
    <?= view('layouts/header') ?>
    <?= view('layouts/sidebar') ?>
    <div class="main-content">
        <div class="page-content tf" id="tf">
            <div class="container-fluid">

                <?= view('layouts/tourdesk_menu') ?>

                <!-- tf-resdesk-r2 design v2 (Shantha's mock-up, 12 Nov 2026): one column, purple header → three steps → the tour → its hotels.
                     Every style of this page sits under .rd3, so the Tour Desk's compact table styles never reach it. -->
                <div class="rd3">
                  <div class="rd3-shell">
                    <header class="rd3-hero">
                        <div class="rd3-brand">Avenir Holidays</div>
                        <div class="rd3-desk">Hotel Reservation Desk</div>
                    </header>
                    <nav class="rd3-nav" aria-label="Reservation and payment steps">
                        <a class="rd3-navb on" href="<?= base_url('tour/hotel_bookings') ?>">Hotel reservations</a>
                        <a class="rd3-navb" href="<?= base_url('tour/tour_accounts/payment_approval_v2') ?>">Payment Approval</a>
                        <a class="rd3-navb" href="<?= base_url('report/bank_payment_check') ?>">Bank Payment</a>
                    </nav>
                    <main class="rd3-main">
                <?php if (empty($installed)): ?>
                <div class="rd3-dept">The Tour Desk tables are not at the current version. Open <a href="<?= base_url($intake_url ?? 'tour/intake') ?>">Inquiries</a> and press Install; this screen then works.</div>
                <?php else: ?>

                <!-- tf-resdesk-r2 (13 Nov 2026, Shantha's mock-up): the Reservation Desk — Hotel Reservations (Pending / Confirmed) and Other Reservations -->
                <section id="list_card" class="rdl">
                    <h1 class="rdl-h1">Reservation Desk</h1>
                    <div class="rdl-tabs" role="tablist">
                        <button class="rdl-tab on" data-desk="hotel" role="tab" type="button">Hotel Reservations</button>
                        <button class="rdl-tab" data-desk="other" role="tab" type="button">Other Reservations</button>
                    </div>
                    <div class="rdl-sub" id="rdl_sub">
                        <button class="rdl-subb on" data-list="pending" type="button">Pending Reservations</button>
                        <button class="rdl-subb" data-list="confirmed" type="button">Confirmed Hotel Reservations</button>
                    </div>
                    <div class="rdl-search">
                        <label class="rdl-q"><svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#78B848" stroke-width="2.3" stroke-linecap="round" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.3"/><path d="M15.3 15.3L20 20"/></svg><input id="f_q" placeholder="Inquiry number or customer name" aria-label="Inquiry number or customer name"></label>
                        <label class="rdl-date"><svg class="rdl-ico" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#78B848" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/></svg><span class="rdl-dtxt" data-empty="Arrival from">Arrival from</span><svg class="rdl-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#222" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg><input type="date" id="f_from" aria-label="Arrival from"></label>
                        <label class="rdl-date"><svg class="rdl-ico" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#78B848" stroke-width="1.9" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/></svg><span class="rdl-dtxt" data-empty="Arrival to">Arrival to</span><svg class="rdl-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#222" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg><input type="date" id="f_to" aria-label="Arrival to"></label>
                        <button class="rdl-go" id="f_search" type="button">Search</button>
                    </div>
                    <div class="rdl-table" role="table">
                        <div class="rdl-head" role="row"><span>Inquiry Number</span><span>Status</span><span>Agent Name</span><span>Client Name</span><span></span></div>
                        <div id="tours"><div class="rdl-empty">Loading…</div></div>
                    </div>
                    <div class="rdl-legend" id="rdl_legend"></div>
                    <div class="rdl-pager">
                        <label class="rdl-rpp">Rows per page: <span class="rdl-sel"><select id="f_per"><option>10</option><option>25</option><option>50</option></select><svg class="rdl-chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#222" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></span></label>
                        <span class="rdl-sep"></span><span id="rdl_showing"></span>
                        <span class="rdl-pbtns"><button type="button" id="pg_prev"><b>‹</b> Previous</button><button type="button" id="pg_next">Next <b>›</b></button></span>
                    </div>
                    <span id="rd_testbox" hidden></span><button id="f_clear" type="button" hidden></button>
                    <select id="f_handler" hidden><option value=""></option></select><div id="chips" hidden></div>
                </section>

                <!-- the tour opened: its hotels, then everything else folded under Tour details -->
                <section id="tour_view" style="display:none">
                    <div id="tv_head"></div>
                    <div class="rd3-dept">Hotel Reservations department · Confirm each hotel after receiving its reply.</div>
                    <div class="rd3-hotels" id="rows"><div class="rd3-empty">Loading…</div></div>
                    <details class="rd3-fold rd3-more" id="rd3_more">
                        <summary>Tour details</summary>
                        <div id="tv_more"></div>
                        <details id="tv_details_wrap" class="rd3-fold"><summary>The whole tour — programme, passengers, flights, requests</summary><div id="tv_details" class="mt-2"></div></details>
                    <div class="tf-card mt-3" id="acts_card">
                        <div class="tf-card-h">
                            <div>
                                <h6>Activities &amp; tickets</h6>
                                <div class="help">Entrance tickets, train tickets, safaris, excursions and anything else the quotation carried as a line — each to be requested and confirmed, given its <b>tickets from stock</b> by the party size (an attraction under Ticket Stock control), marked <b>pay at gate</b> where the entrance is paid at the counter on the day, or marked <b>nothing to arrange</b> with a word on why.</div>
                            </div>
                            <div><button class="btn btn-light btn-sm" id="a_add" title="Something to arrange that the quotation did not carry as a line"><i class="bi bi-plus-lg"></i> Add a line</button></div>
                        </div>
                        <div style="overflow-x:auto">
                            <table class="tbl">
                                <thead><tr><th style="width:110px">Day · date</th><th>What</th><th style="width:60px">Party</th><th style="width:110px">Paid</th><th style="width:220px">Status</th><th style="width:120px"></th></tr></thead>
                                <tbody id="a_rows"><tr><td colspan="6" class="empty">Loading…</td></tr></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- tf-supmail-r1: the reservation requests to the activity suppliers and the restaurants -->
                    <div class="tf-card mt-3" id="sreq_card">
                        <div class="tf-card-h">
                            <div>
                                <h6>Booking requests — activities and restaurants</h6>
                                <div class="help">Prepared from the record in the saved wording as soon as a line is on the tour. Sent by e-mail where the master holds an address; otherwise the same words are here to send by hand or print &mdash; then record what you did. <b>A request is never a confirmation</b>: the line stays pending until you record the supplier's own answer above.</div>
                            </div>
                            <div><button class="btn btn-light btn-sm" id="sreq_refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button></div>
                        </div>
                        <div class="tf-card-b" id="sreq_body"><div class="empty">Loading…</div></div>
                    </div>
                    </details>
                </section>

                <?php endif; ?>
                    </main>
                  </div>
                </div>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>
</div>

<!-- Manage: correct a card in place (Management) -->
<div class="modal fade" id="m_edit" tabindex="-1"><div class="modal-dialog"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="e_title">Correct this stay</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-md-8"><label class="form-label help">Hotel</label><input class="form-control form-control-sm" id="e_hotel"></div>
            <div class="col-md-4"><label class="form-label help">Area</label><input class="form-control form-control-sm" id="e_area"></div>
            <div class="col-md-4"><label class="form-label help">Check-in</label><input type="date" class="form-control form-control-sm" id="e_in"></div>
            <div class="col-md-4"><label class="form-label help">Check-out</label><input type="date" class="form-control form-control-sm" id="e_out"></div>
            <div class="col-md-4"><label class="form-label help">Meal plan</label><select class="form-select form-select-sm" id="e_meal"><option value="">Not stated</option><option>RO</option><option>BB</option><option>HB</option><option>FB</option><option>AI</option></select></div>
            <div class="col-md-3"><label class="form-label help">Rooms</label><input type="number" min="0" class="form-control form-control-sm" id="e_rooms"></div>
            <div class="col-md-6"><label class="form-label help">Rooms note</label><input class="form-control form-control-sm" id="e_rooms_note"></div>
            <div class="col-md-3"><label class="form-label help">Party</label><input type="number" min="0" class="form-control form-control-sm" id="e_pax"></div>
            <div class="col-12"><label class="form-label help">Agent's notes</label><textarea class="form-control form-control-sm" id="e_special" rows="2"></textarea></div>
            <div class="col-12"><label class="form-label help">Why <span class="text-muted">(stays on the history)</span></label><input class="form-control form-control-sm" id="e_note" maxlength="200" placeholder="e.g. agent moved the check-in by phone"></div>
        </div>
        <div class="help mt-2">This corrects the card the Hotel Desk works from. The quotation is not changed — amend it from the Tour Board if the sale itself changed. Every change here is written to the history with your name.</div>
        <input type="hidden" id="e_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="e_ok">Save correction</button></div>
</div></div></div>

<!-- the stay -->
<div class="modal fade" id="m_card" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="c_title">Stay</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="c_body"></div>
</div></div></div>

<!-- tf-resdesk-r2: Confirm Booking — the hotel's reply recorded; only then is the payment request raised -->
<div class="modal fade" id="m_cb" tabindex="-1"><div class="modal-dialog"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="cb_title">Record Hotel Confirmation</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="help mb-2" id="cb_stay"></div>
        <div class="row g-2">
            <div class="col-md-7"><label class="form-label help" for="cb_ref">Hotel's confirmation reference <span class="text-danger">*</span></label><input class="form-control form-control-sm" id="cb_ref" maxlength="100" placeholder="e.g. QHK/2612/0458"></div>
            <div class="col-md-5"><label class="form-label help" for="cb_on">Confirmed by the hotel on</label><input type="date" class="form-control form-control-sm" id="cb_on"></div>
            <div class="col-12"><label class="form-label help" for="cb_with">Confirmed by (hotel person)</label><input class="form-control form-control-sm" id="cb_with" maxlength="160" placeholder="e.g. Ms. Perera, Reservations"></div>
            <div class="col-12"><label class="form-label help" for="cb_reply">Hotel's reply <span class="text-danger">*</span> <span class="text-muted">(paste what the hotel wrote, or attach its e-mail below)</span></label><textarea class="form-control form-control-sm" id="cb_reply" rows="3" maxlength="4000"></textarea></div>
            <div class="col-12"><label class="form-label help" for="cb_file">Evidence — the hotel's confirmation (PDF, picture, .eml / .msg, max 20 MB)</label><input type="file" class="form-control form-control-sm" id="cb_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.eml,.msg,.txt,.doc,.docx"></div>
            <div class="col-12"><label class="form-label help" for="cb_note">Note</label><input class="form-control form-control-sm" id="cb_note" maxlength="300"></div>
        </div>
        <div class="help mt-2">Your name and the time are recorded with the confirmation. The stay's hotel payment request is then raised to <b>Payment Approval</b>; the payment voucher is created only after the payment is approved there.</div>
        <input type="hidden" id="cb_id">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-sm" style="background:#7B2382;color:#fff" id="cb_ok">Confirm Hotel</button></div>
</div></div></div>

<!-- a letter as it was sent -->
<div class="modal fade" id="m_msg" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="g_title">Letter</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="g_body"></div>
</div></div></div>

<!-- an activity or ticket line -->
<div class="modal fade" id="m_act" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="a_title">Activity</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" id="a_body"></div>
</div></div></div>

<!-- a line the desk adds itself -->
<div class="modal fade" id="m_addact" tabindex="-1"><div class="modal-dialog"><div class="modal-content tf">
    <div class="modal-header"><h6 class="modal-title" id="aa_title">Add something to arrange</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-12"><label class="form-label help">What</label><input class="form-control form-control-sm" id="aa_what" placeholder="e.g. Train tickets Ella – Kandy, 2nd class"></div>
            <div class="col-md-5"><label class="form-label help">Kind</label><select class="form-select form-select-sm" id="aa_kind"><option value="entrance">Entrance ticket</option><option value="train">Train ticket</option><option value="safari">Safari</option><option value="visit">Sightseeing visit</option><option value="excursion">Excursion</option><option value="ayurveda">Ayurveda / spa</option><option value="lunch">Lunch</option><option value="dinner">Dinner</option><option value="other" selected>Other</option></select></div>
            <div class="col-md-3"><label class="form-label help">Day of tour</label><input type="number" min="1" class="form-control form-control-sm" id="aa_day"></div>
            <div class="col-md-4"><label class="form-label help">Party</label><input type="number" min="0" class="form-control form-control-sm" id="aa_pax"></div>
            <div class="col-12"><label class="form-label help">Note</label><input class="form-control form-control-sm" id="aa_note" maxlength="500"></div>
        </div>
        <div class="help mt-2">For something to arrange that the quotation did not carry as a line of its own. It is not carried back into the quotation — if the sale itself changed, ask Management to amend the tour.</div>
        <input type="hidden" id="aa_tour">
    </div>
    <div class="modal-footer"><button class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary btn-sm" id="aa_ok">Add to the desk</button></div>
</div></div></div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
<script src="<?= base_url('assets/tourmail/tour-mails.js') ?>?r1"></script>
<script>
(function () {
    'use strict';
    const BASE = '<?= base_url($page_url ?? 'tour/hotel_bookings') ?>';
    const INSTALLED = <?= !empty($installed) ? 'true' : 'false' ?>;
    const SEES_RATES = <?= !empty($sees_rates) ? 'true' : 'false' ?>;
    const CAN_MANAGE = <?= !empty($can_manage) ? 'true' : 'false' ?>;
    const CAN_INTAKE = <?= !empty($can_intake) ? 'true' : 'false' ?>;   // tf-walkfix-r1: the inquiry is offered only to a sign-in that may open it
    const MANAGE_BASE = '<?= base_url($manage_url ?? 'tour/manage') ?>';
    const INTAKE = '<?= base_url($intake_url ?? 'tour/intake') ?>';
    const CAN_TICKETS = <?= !empty($can_tickets) ? 'true' : 'false' ?>;   // tf-tickets-r1: the Ticket Stock screen, linked only to a sign-in that may open it
    const TICKETS = '<?= base_url($tickets_url ?? 'tour/ticket_stock') ?>';
    if (!INSTALLED) return;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const toast = (icon, text) => Swal.fire({ icon, text, timer: icon === 'success' ? 2800 : undefined, showConfirmButton: icon !== 'success' });
    const api = async (path, opts) => { const r = await fetch(BASE + path, Object.assign({ headers: { 'X-Requested-With':'XMLHttpRequest' } }, opts || {})); return r.json(); };
    const post = (path, body) => api(path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) });
    const fmt = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short' }); };
    const fmtY = d => { if (!d) return '—'; const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }); };
    const when = s => s ? String(s).slice(0, 16) : '';

    // the stay's steps, in the desk's words: pending → sent → confirmed → voucher issued; a problem says which
    const STATUS = {
        to_book: ['To book', 'pill-grey'], requested: ['Sent — awaiting reply', 'pill-blue'], confirmed: ['Confirmed', 'pill-teal'],
        voucher_issued: ['Voucher issued', 'pill-green'], problem: ['Problem', 'pill-red'], cancelled: ['Cancelled', 'pill-grey'],
    };
    const PROBLEM_WORD = { REJECTED: 'Rejected / unavailable', ALTERNATIVE: 'Alternative required' };
    const CHIPS = [['pending', 'Pending'], ['completed', 'Completed'], ['cancelled', 'Cancelled'], ['all', 'All']];
    let WHICH = 'pending', REASONS = [], CARD = null, TOUR = null, TOURS = [];

    // =====================================================================
    //  The list: one row per confirmed tour
    // =====================================================================
    function prog(x, word, problemWord) {
        if (!x || !x.of) return `<span class="help">—</span>`;
        const pct = Math.round(100 * x.done / x.of);
        return `<span class="prog ${x.problem ? 'problem' : ''}" title="${esc(x.done)} of ${esc(x.of)} ${esc(word)}"><span class="bar"><i style="width:${pct}%"></i></span><b>${x.done}</b>/${x.of}${x.problem ? ` <span class="pill pill-red">${x.problem} ${esc(problemWord || 'problem')}</span>` : ''}</span>`;
    }
    // tf-resdesk-r2: hotel confirmation progress — a block per hotel: green confirmed, red e-mail failed / not sent, grey awaiting
    function rdProg(r) {
        if (!r || !r.total) return '<span>No hotel on this tour</span>';
        const bits = [`${r.confirmed} of ${r.total} hotels confirmed`];
        if (r.awaiting) bits.push(`${r.awaiting} awaiting hotel confirmation`);
        if (r.requested) bits.push(`${r.requested} awaiting payment approval`);
        if (r.approved) bits.push(`${r.approved} payment approved`);
        if (r.paid) bits.push(`${r.paid} paid`);
        if (r.unraised) bits.push(`${r.unraised} payment request not raised`);
        const bad = [];
        if (r.failed) bad.push(`${r.failed} email failed`);
        if (r.not_sent) bad.push(`${r.not_sent} not sent`);
        if (r.problem) bad.push(`${r.problem} problem`);
        return `<span>${esc(bits.join(' · '))}</span>${bad.length ? `<span class="rd3-bad"> · ${esc(bad.join(' · '))}</span>` : ''}`;
    }
    // A cancelled tour is flagged in red wherever it shows, with the reason, who cancelled it and when
    const cancelFlag = (t, big) => t.stage !== 'cancelled' ? '' : (big
        ? `<div class="cancel-flag"><i class="bi bi-x-octagon-fill"></i> <b>CANCELLED</b> ${t.cancelled_at ? when(t.cancelled_at) : ''}${t.cancelled_by ? ' by ' + esc(t.cancelled_by) : ''}${t.cancel_why ? ' — ' + esc(t.cancel_why) : ''}<div class="help" style="color:inherit">Do no further work on this tour: every card is marked cancelled. Work already done stays on the trail.</div></div>`
        : `<span class="pill pill-red" title="${esc((t.cancelled_at ? when(t.cancelled_at) : '') + (t.cancelled_by ? ' by ' + t.cancelled_by : '') + (t.cancel_why ? ' — ' + t.cancel_why : ''))}"><i class="bi bi-x-octagon-fill"></i> CANCELLED</span>${t.cancel_why ? `<div class="help" style="color:var(--tf-red)">${esc(t.cancel_why)}</div>` : ''}`);
    function workPill(t) {
        if (t.stage === 'cancelled') return cancelFlag(t, false);
        if (t.work === 'complete') return `<span class="pill pill-green">Complete</span><div class="help">${when(t.work_at)}${t.work_by ? ' · ' + esc(t.work_by) : ''}</div>`;
        if (t.work === 'none') return '<span class="pill pill-grey">Nothing for this desk</span>';
        const s = t.strip || {};
        if (t.problems) return `<span class="pill pill-red">Problem</span><div class="help">${t.problems} to sort out</div>`;
        const started = (s.hotels && (s.hotels.done || s.hotels.confirmed)) || (s.activities && s.activities.done);
        return `<span class="pill ${started ? 'pill-blue' : 'pill-amber'}">${started ? 'In progress' : 'Pending'}</span>${s.reservations_ready ? '<div class="help">ready to mark complete</div>' : ''}`;
    }
    // the red count on the bar is this desk's pending tours: it moves the moment the work does
    function updateBadge(n) {
        const tab = document.querySelector('.tdbar .td-item.on');
        if (!tab || n === undefined || n === null) return;
        let b = tab.querySelector('.td-badge');
        if (Number(n) > 0) {
            if (!b) { b = document.createElement('span'); b.className = 'td-badge'; tab.appendChild(b); }
            b.textContent = String(n);
        } else if (b) {
            b.remove();
        }
    }
    function renderChips(counts) {
        document.getElementById('chips').innerHTML = CHIPS.map(([k, l]) => {
            const n = counts[k];
            return `<span class="chip ${WHICH === k ? 'on' : ''}" data-chip="${k}">${l}${n !== undefined ? `<span class="n">${n}</span>` : ''}</span>`;
        }).join('');
        document.querySelectorAll('[data-chip]').forEach(c => c.addEventListener('click', () => { WHICH = c.dataset.chip; loadTours(); }));
    }
    const filterQs = () => new URLSearchParams({ which: WHICH, q: document.getElementById('f_q').value || '', handler: document.getElementById('f_handler').value || '', from: document.getElementById('f_from').value || '', to: document.getElementById('f_to').value || '' }).toString();
    // tf-resdesk-r2: the Reservation Desk lists — Pending Reservations, Confirmed Hotel Reservations, Other Reservations.
    // The light is the server's, worked out from the bookings; opening a tour changes nothing in it.
    let DESK = 'hotel', LIST = 'pending', PAGE = 1, PER = 10;
    const LIGHT = { red: 'Red', yellow: 'Yellow', green: 'Green' };
    const light = c => `<span class="rdl-light" role="img" aria-label="${c ? LIGHT[c] : 'No status'}"><i class="${c === 'red' ? 'on-r' : ''}"></i><i class="${c === 'yellow' ? 'on-y' : ''}"></i><i class="${c === 'green' ? 'on-g' : ''}"></i></span>`;
    function legend() {
        const h = [['red', 'Red:', 'Not sent'], ['yellow', 'Yellow:', 'Awaiting confirmation'], ['green', 'Green:', 'All hotels confirmed']];
        const o = [['red', 'Red:', 'Not requested'], ['yellow', 'Yellow:', 'Awaiting confirmation'], ['green', 'Green:', 'All confirmed']];
        if (DESK !== 'other' && LIST === 'confirmed') { document.getElementById('rdl_legend').innerHTML = '<span><i class="dot green"></i><b>All hotels confirmed</b></span>'; return; }
        document.getElementById('rdl_legend').innerHTML = (DESK === 'other' ? o : h).map(([c, b, t]) => `<span><i class="dot ${c}"></i><b>${b}</b> ${t}</span>`).join('');
    }
    async function loadTours() {
        const list = DESK === 'other' ? 'other' : LIST;
        const qs = new URLSearchParams({ list, q: document.getElementById('f_q').value.trim(), from: document.getElementById('f_from').value || '', to: document.getElementById('f_to').value || '', page: PAGE, per: PER }).toString();
        const j = await api('/lists?' + qs);
        if (j.status !== 200) { toast('error', j.message); return; }
        const d = j.data;
        const tbx = document.getElementById('rd_testbox'); if (tbx) tbx.style.display = d.test_mailbox ? '' : 'none';
        document.getElementById('f_q').placeholder = list === 'pending' ? 'Search by inquiry number, agent or client' : 'Inquiry number or customer name';
        updateBadge(d.counts.pending);
        legend();
        const tb = document.getElementById('tours');
        TOURS = d.rows;
        const searching = document.getElementById('f_q').value.trim() || document.getElementById('f_from').value || document.getElementById('f_to').value;
        if (!d.rows.length) {
            tb.innerHTML = `<div class="rdl-empty">${searching ? 'No reservation matches the search.' : (list === 'pending' ? 'No pending hotel reservations.' : list === 'confirmed' ? 'No confirmed hotel reservations yet.' : 'No other reservations.')}</div>`;
        } else {
            tb.innerHTML = d.rows.map(t => {
                const c = list === 'other' ? t.other_light : t.hotel_light;
                const what = list === 'other' ? `${t.other_done} of ${t.other_total} done` : `${t.hotels_confirmed} of ${t.hotels} hotel${t.hotels === 1 ? '' : 's'} confirmed${t.hotels_unsent ? ' · ' + t.hotels_unsent + ' not sent' : ''}`;
                return `<div class="rdl-row" role="row" data-tour="${t.id}">
                    <span class="rdl-inq">${esc(t.inquiry_no || t.tour_no)}</span>
                    <span title="${esc(what)}">${light(c)}</span>
                    <span>${esc(t.agent_name || '—')}</span>
                    <span class="rdl-client" title="${esc(what)} · arrival ${fmtY(t.arrival_date)}">${esc(t.client_name || '—')}</span>
                    <span class="rdl-oc"><button class="rdl-open" data-open-tour="${t.id}" type="button">Open <span aria-hidden="true">→</span></button></span>
                </div>`;
            }).join('');
            tb.querySelectorAll('[data-open-tour]').forEach(b => b.addEventListener('click', () => openTour(Number(b.dataset.openTour), false, DESK === 'other')));
        }
        const a = d.total ? (d.page - 1) * d.per + 1 : 0, z = Math.min(d.total, d.page * d.per);
        document.getElementById('rdl_showing').textContent = `Showing ${a}–${z} of ${d.total}`;
        document.getElementById('pg_prev').disabled = d.page <= 1;
        document.getElementById('pg_next').disabled = d.page >= d.pages;
    }
    document.querySelectorAll('.rdl-tab').forEach(b => b.addEventListener('click', () => { DESK = b.dataset.desk; PAGE = 1;
        document.querySelectorAll('.rdl-tab').forEach(x => x.classList.toggle('on', x === b));
        document.getElementById('rdl_sub').style.display = DESK === 'other' ? 'none' : ''; loadTours(); }));
    document.querySelectorAll('.rdl-subb').forEach(b => b.addEventListener('click', () => { LIST = b.dataset.list; PAGE = 1;
        document.querySelectorAll('.rdl-subb').forEach(x => x.classList.toggle('on', x === b)); loadTours(); }));
    document.getElementById('f_per').addEventListener('change', e => { PER = Number(e.target.value); PAGE = 1; loadTours(); });
    document.getElementById('pg_prev').addEventListener('click', () => { if (PAGE > 1) { PAGE--; loadTours(); } });
    document.getElementById('pg_next').addEventListener('click', () => { PAGE++; loadTours(); });
    document.getElementById('f_search').addEventListener('click', () => { PAGE = 1; loadTours(); });
    document.getElementById('f_q').addEventListener('keydown', e => { if (e.key === 'Enter') { PAGE = 1; loadTours(); } });

    // =====================================================================
    //  The tour opened: its hotels and its activity lines
    // =====================================================================
    let ITEMS = [], A_ITEMS = [], A_REASONS = [], ACT = null, DETAILS = {};
    async function openTour(id, quiet, other) {
        const j = await api('/tour/' + id + '/work');
        if (j.status !== 200) { toast('error', j.message); return; }
        TOUR = j.data.tour; REASONS = j.data.reasons || []; A_REASONS = j.data.a_reasons || [];
        ITEMS = j.data.stays || []; A_ITEMS = j.data.activities || [];
        document.getElementById('list_card').style.display = 'none';
        document.getElementById('tour_view').style.display = '';
        // tf-refresh-r1: the tour is in the address (a refresh opens it again, read fresh); opening one is a step Back can undo
        if (!quiet) { if (window.TfView) TfView.setParams({ tour: id }, { push: true }); else { try { history.replaceState(null, '', location.pathname + '?tour=' + id); } catch (e) {} } }
        renderTourHead();
        renderStays();
        renderActs();
        loadSupplierRequests(id);
        loadDetails(id, 'tv_details');
        document.querySelector('.rd3').classList.add('in-tour');
        if (other) { const m = document.getElementById('rd3_more'); m.open = true; setTimeout(() => document.getElementById('acts_card').scrollIntoView({ block: 'start' }), 60); }
        else if (!quiet) window.scrollTo({ top: 0 });
    }

    // =====================================================================
    //  tf-supmail-r1: the reservation requests to the activity suppliers and the restaurants
    //  Reading this card prepares what is missing; it never sends. Sending is a press, or the
    //  workflow's own automatic step where that is switched on for the installation.
    // =====================================================================
    let SREQ = null;
    async function loadSupplierRequests(tourId) {
        const box = document.getElementById('sreq_body');
        if (!box) { return; }
        box.innerHTML = '<div class="empty">Loading…</div>';
        const j = await api('/tour/' + tourId + '/supplier_requests');
        if (j.status !== 200) { box.innerHTML = '<div class="empty">' + esc(j.message || 'The requests could not be read.') + '</div>'; return; }
        SREQ = j.data || {};
        renderSupplierRequests();
    }

    function renderSupplierRequests() {
        const box = document.getElementById('sreq_body');
        if (!box || !SREQ) { return; }
        if (SREQ.installed === false) {
            box.innerHTML = '<div class="empty">This install does not have the supplier-request table yet. Open Tour Inquiry as the administrator and press Install now.</div>';
            return;
        }
        const msgs = (SREQ.messages || []).filter(m => m.kind === 'request');
        const label = { prepared: ['pill-amber', 'Prepared — not sent'], sent: ['pill-green', 'Sent'], failed: ['pill-red', 'NOT sent'], recorded: ['pill-grey', 'Sent by hand'] };
        box.innerHTML =
              (SREQ.auto_send === false ? '<div class="note mb-2">Automatic sending is switched off on this installation, so these letters are written and wait here. Send each one when you are ready.</div>' : '')
            + ((SREQ.missing || []).length ? '<div class="note mb-2"><b>Still missing:</b><ul class="mb-0">' + SREQ.missing.map(w => '<li>' + esc(w) + '</li>').join('') + '</ul></div>' : '')
            + (msgs.length
                ? '<div style="overflow-x:auto"><table class="tbl"><thead><tr><th style="width:190px">Who</th><th>The request</th><th style="width:150px">State</th><th style="width:230px"></th></tr></thead><tbody>'
                  + msgs.map(m => {
                      const [cls, text] = label[m.state] || ['pill-grey', m.state];
                      return '<tr><td><b>' + esc(m.party_name || '') + '</b>'
                        + '<div class="help">' + (m.party === 'restaurant' ? 'restaurant' : 'activity supplier') + '</div>'
                        + '<div class="help">' + (m.to_addr ? esc(m.to_addr) : '<span class="pill pill-amber">no address</span>') + '</div></td>'
                        + '<td><div class="help">' + esc(m.subject || '') + '</div>'
                        + '<details><summary class="help" style="cursor:pointer">The words that go</summary>'
                        + '<pre style="white-space:pre-wrap;font:inherit;font-size:12.5px;margin:6px 0 0">' + esc(m.body || '') + '</pre></details></td>'
                        + '<td><span class="pill ' + cls + '">' + esc(text) + '</span>'
                        + '<div class="help">' + esc(String(m.at || '').slice(0, 16)) + (m.by_name ? ' · ' + esc(m.by_name) : '') + '</div>'
                        + (m.error ? '<div class="help" style="color:var(--tf-red)">' + esc(m.error) + '</div>' : '')
                        + '<div class="help">a request is not a confirmation</div>'
                        + (m.hold ? '<div class="help" style="color:var(--tf-red);margin-top:4px"><b>Held:</b> ' + esc(m.hold) + '</div>' : '')
                        + '</td>'
                        + '<td style="white-space:nowrap">'
                        + (m.state !== 'sent' && m.to_addr && !m.hold ? '<button class="btn btn-light btn-sm" data-sqsend="' + m.id + '">Send by e-mail</button> ' : '')
                        + (m.hold ? '<span class="pill pill-red">cannot be sent yet</span> ' : '')
                        + (m.state === 'prepared' ? '<button class="btn btn-light btn-sm" data-sqedit="' + m.id + '">Edit the words</button> ' : '')
                        + '<button class="btn btn-light btn-sm" data-sqhand="' + m.id + '">Sent by hand</button> '
                        + '<button class="btn btn-light btn-sm" data-sqreply="' + m.id + '">Record the answer</button></td></tr>';
                  }).join('')
                  + '</tbody></table></div>'
                : '<div class="empty">No activity or restaurant line on this tour yet, so there is nothing to request.</div>');
        document.querySelectorAll('[data-sqsend]').forEach(b => b.addEventListener('click', () => sqSend(b.dataset.sqsend)));
        document.querySelectorAll('[data-sqedit]').forEach(b => b.addEventListener('click', () => sqEdit(b.dataset.sqedit)));
        document.querySelectorAll('[data-sqhand]').forEach(b => b.addEventListener('click', () => sqNote(b.dataset.sqhand, 'request')));
        document.querySelectorAll('[data-sqreply]').forEach(b => b.addEventListener('click', () => sqNote(b.dataset.sqreply, 'reply')));
        const r = document.getElementById('sreq_refresh');
        if (r && !r.dataset.wired) { r.dataset.wired = '1'; r.addEventListener('click', () => { if (TOUR) loadSupplierRequests(TOUR.id); }); }
    }

    // this one letter's own words; the saved template on Master Files is never touched
    async function sqEdit(id) {
        const m = ((SREQ && SREQ.messages) || []).find(x => Number(x.id) === Number(id));
        if (!m) { return; }
        const subject = window.prompt('Subject for this letter only:', m.subject || '');
        if (subject === null) { return; }
        const body = window.prompt('The message for this letter only (the saved template is not changed):', m.body || '');
        if (body === null) { return; }
        const j = await api('/supplier_request/' + id + '/edit', { subject: subject, body: body });
        toast(j.status === 200 ? 'ok' : 'error', j.message || '');
        if (TOUR) { loadSupplierRequests(TOUR.id); }
    }

    async function sqSend(id) {
        const j = await api('/supplier_request/' + id + '/send', {});
        toast(j.status === 200 ? 'ok' : 'error', j.message || '');
        if (TOUR) { loadSupplierRequests(TOUR.id); }
    }

    async function sqNote(id, kind) {
        const what = kind === 'reply' ? 'What did they answer?' : 'How did it go, and anything worth keeping?';
        const note = window.prompt(what, '');
        if (note === null) { return; }
        const j = await api('/supplier_request/' + id + '/record', { channel: kind === 'reply' ? 'other' : 'whatsapp', note: note, kind: kind });
        toast(j.status === 200 ? 'ok' : 'error', j.message || '');
        if (TOUR) { loadSupplierRequests(TOUR.id); }
    }

    function backToList() {
        TOUR = null;
        document.getElementById('tour_view').style.display = 'none';
        document.getElementById('list_card').style.display = '';
        document.querySelector('.rd3').classList.remove('in-tour');
        if (window.TfView) { if (!backToList.fromHistory) TfView.setParams({ tour: null }, { push: true }); } else { try { history.replaceState(null, '', location.pathname); } catch (e) {} }
        loadTours();
    }
    async function refreshTour() {
        if (!TOUR) { loadTours(); return; }
        await openTour(TOUR.id, true);
        loadTours();   // the counts on the chips and the bar move with the work
    }
    function renderTourHead() {
        const t = TOUR, s = t.strip || {};
        const mark = t.stage === 'cancelled' ? '' : (s.reservations_confirmed
            ? `<span class="pill pill-green" style="font-size:12px;padding:4px 10px"><i class="bi bi-patch-check-fill"></i> Hotel work complete · ${esc(s.reservations_confirmed_by || '')} ${when(s.reservations_confirmed_at)}</span> <button class="btn btn-light btn-sm" data-unmark="${t.id}" title="Take the mark off (say why)">undo</button>`
            : (s.reservations_ready
                ? `<button class="rd3-b" data-mark="${t.id}">Mark hotel work complete</button>`
                : ''));
        const guests = (() => { const a = Number(t.pax_adults || 0), c = Number(t.pax_children || 0); if (a || c) return [a ? a + ' adult' + (a === 1 ? '' : 's') : '', c ? c + ' child' + (c === 1 ? '' : 'ren') : ''].filter(Boolean).join(' + '); return (t.pax_total ?? '?') + ' guests'; })();
        document.getElementById('tv_head').innerHTML = `
            <h1 class="rd3-h1">${esc(t.tour_no)} · Hotel reservations</h1>
            <p class="rd3-summary">${esc(t.tour_name || t.agent_name || '—')} · ${esc(guests)} · Arrival ${fmtY(t.arrival_date)}</p>
            ${cancelFlag(t, true)}
            <button class="rd3-b" id="tv_back" type="button">← Tour orders</button>`;
        document.getElementById('tv_more').innerHTML = `
            <dl class="rd3-dl">
                <div><dt>Inquiry</dt><dd>${esc(t.inquiry_no || '—')}</dd></div>
                <div><dt>Agent</dt><dd>${esc(t.agent_name || '—')}</dd></div>
                <div><dt>Dates</dt><dd>${fmtY(t.arrival_date)} – ${fmtY(t.departure_date)} · ${t.nights ?? '?'} nights</dd></div>
                <div><dt>Party</dt><dd>${esc(t.pax_total ?? '?')} pax</dd></div>
                <div><dt>Handled by</dt><dd>${t.handler_name ? esc(t.handler_name) : '—'}</dd></div>
                <div><dt>Hotels</dt><dd>${t.rd ? rdProg(t.rd) : '—'}</dd></div>
            </dl>
            <div class="rd3-btns">${t.stage === 'cancelled' ? '' : `<button class="rd3-b" id="tv_mailall" type="button">Email all hotels</button>`}<button class="rd3-b" id="tv_mails" type="button">Mails of this tour</button><a class="rd3-b" href="${BASE}/tour/${t.id}/programme" target="_blank">Print the programme</a></div>
            <div class="rd3-btns">${mark}</div>
            <div class="rd3-strip" id="tv_strip">${window.tdStrip(s, { compact: true })}</div>`;
        document.getElementById('tv_back').addEventListener('click', backToList);
        // tf-tourmail-r1: every hotel's request in one go (listed first, sent on OK), and the tour's mails as they went
        const ma = document.getElementById('tv_mailall'); if (ma) ma.addEventListener('click', () => mailAllHotels(t));
        document.getElementById('tv_mails').addEventListener('click', () => window.AvhTourMails && window.AvhTourMails.showTourMails(BASE, t.id, t.tour_no));
        wireMarks(document.getElementById('tv_more'));
    }
    /** tf-tourmail-r1: the booking request to every hotel of the tour still to book — the list first, sent only on OK. */
    async function mailAllHotels(t) {
        if (!window.AvhTourMails) return;
        const pj = await api('/tour/' + t.id + '/mail_plan');
        if (pj.status !== 200) { toast('error', pj.message); return; }
        const open = (pj.data.stays || []).filter(s => !s.already);
        if (!open.length) { await Swal.fire({ icon: 'info', title: 'Nothing to send', text: 'Every hotel of ' + t.tour_no + ' has been requested already. A stay is sent again only from its own card (Send).' }); return; }
        const choice = await window.AvhTourMails.review(pj.data, { title: 'Email all hotels — ' + t.tour_no, intro: 'Each hotel below that is still to book gets its booking request by e-mail when you press <b>Send</b>. Check the addresses; untick one to leave it out.', ok: 'Send', focusCancel: true });
        if (!choice) return;
        Swal.fire({ title: 'Sending…', html: 'The booking requests are going from your mailbox — this can take a minute.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let j;
        try { j = await post('/tour/' + t.id + '/mail_all', choice); }
        catch (e) { await Swal.fire({ icon: 'error', title: 'No clear answer from the server', html: 'Before sending again, open <b>Mails</b> on this tour to see which requests went.' }); refreshTour(); return; }
        Swal.close();
        if (j.status !== 200) { toast('error', j.message); return; }
        const bad = (j.data.results || []).some(r => r.status === 'not_sent');
        await Swal.fire({ icon: bad ? 'warning' : 'success', title: bad ? 'Some requests were NOT sent' : 'Booking requests sent', width: 820, html: `<div style="text-align:left;font-size:13px">${esc(j.message)}${window.AvhTourMails.resultsHtml(j.data.results, [])}</div>`, confirmButtonText: 'Done' });
        if (window.AvhMailAlert) window.AvhMailAlert();
        refreshTour();
    }
    async function markTour(tourId, on) {
        let why = '';
        if (!on) {
            const r = await Swal.fire({ title: 'Take the mark off?', input: 'text', inputPlaceholder: 'Why (required, stays on the trail)', showCancelButton: true, confirmButtonText: 'Take it off' });
            if (!r.isConfirmed) return;
            why = r.value || '';
        } else {
            const r = await Swal.fire({ title: 'Hotel work complete?', text: 'Every stay on this tour has its voucher issued and every activity or ticket line is confirmed or has nothing to arrange. The tour leaves the Hotel Bookings count and stays under Completed; the inquiry, the Transport Desk and the Board will show the mark.', icon: 'question', showCancelButton: true, confirmButtonText: 'Mark it complete', confirmButtonColor: '#1f8a4c' });
            if (!r.isConfirmed) return;
        }
        const j = await post('/tour/' + tourId + '/complete', { on: on ? 1 : 0, why });
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message); refreshTour();
    }
    function wireMarks(el) {
        el.querySelectorAll('[data-mark]').forEach(b => b.addEventListener('click', () => markTour(Number(b.dataset.mark), true)));
        el.querySelectorAll('[data-unmark]').forEach(b => b.addEventListener('click', () => markTour(Number(b.dataset.unmark), false)));
    }

    function pill(j) {
        const st = typeof j === 'string' ? j : j.status;
        const code = typeof j === 'string' ? null : j.problem_code;
        if (st === 'problem' && code && PROBLEM_WORD[code]) return `<span class="pill pill-red">${esc(PROBLEM_WORD[code])}</span>`;
        const [l, c] = STATUS[st] || [st, 'pill-grey']; return `<span class="pill ${c}">${esc(l)}</span>`;
    }
    function flags(j) {
        let out = '';
        if (Number(j.needs_hotel)) out += `<span class="flag bad">Hotel not named — ask the Quotation Desk</span>`;
        if (Number(j.needs_supplier)) out += `<span class="flag">No supplier record — the name is as the agent wrote it; see Incomplete Work</span>`;
        if (Number(j.needs_meal)) out += `<span class="flag">Meal plan not stated — ask the Quotation Desk</span>`;
        if (Number(j.amended)) out += `<span class="flag">The quotation was amended after this card moved — check it</span>`;
        return out;
    }
    // tf-resdesk-r2: one card per hotel — the stay, its stage, the e-mail, the payment dates, the documents, Confirm Booking
    const dt = s => s ? new Date(String(s).replace(' ', 'T')).toLocaleString('en-GB', { day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '—';
    const dOnly = s => s ? fmtY(String(s).slice(0, 10)) : '—';
    // tf-resdesk-r2 design v2: one layout for every hotel — the mock-up's order: name, stay line, status, the three dates,
    // the buttons; everything else under "Booking details". The stage words come from the records (ResDesk::stage).
    const MEAL = { RO: 'Room only', BB: 'Bed and breakfast', HB: 'Half board', FB: 'Full board', AI: 'All inclusive' };
    const range = (a, b) => { if (!a || !b) return '—'; const x = new Date(a + 'T00:00:00'), y = new Date(b + 'T00:00:00');
        const m = d => d.toLocaleDateString('en-GB', { month: 'short' });
        return x.getFullYear() === y.getFullYear() && x.getMonth() === y.getMonth() ? `${x.getDate()}–${y.getDate()} ${m(y)} ${y.getFullYear()}` : `${x.getDate()} ${m(x)} – ${y.getDate()} ${m(y)} ${y.getFullYear()}`; };
    function roomWords(j) {
        const n = String(j.rooms_note || '').split('—')[0].trim();
        if (n) return n.replace(/(\d+)\s*SGL/gi, '$1 single').replace(/(\d+)\s*DBL/gi, '$1 double').replace(/(\d+)\s*TWN/gi, '$1 twin').replace(/(\d+)\s*TPL/gi, '$1 triple').replace(/\s*·\s*/g, ' · ');
        return j.rooms ? j.rooms + ' room' + (Number(j.rooms) === 1 ? '' : 's') : 'Rooms not stated';
    }
    function guestWords(j) {
        const a = Number(TOUR && TOUR.pax_adults || 0), c = Number(TOUR && TOUR.pax_children || 0);
        if (a || c) return [a ? a + ' adult' + (a === 1 ? '' : 's') : '', c ? c + ' child' + (c === 1 ? '' : 'ren') : ''].filter(Boolean).join(' + ');
        return j.pax ? j.pax + ' guests' : '';
    }
    function stageWords(j) {
        const st = j.rd_stage || {}, p = j.rd_pay || {};
        switch (st.key) {
            case 'awaiting': return ['Awaiting hotel confirmation', ''];
            case 'confirmed': return p.stage === 'requested' ? ['Awaiting payment approval', ''] : (p.stage === 'held' ? ['Payment held for rate review', 'warn'] : ['Hotel confirmed — payment request not raised', 'warn']);
            case 'approved': return ['Payment approved — with Bank Payment', ''];
            case 'paid': return ['Paid', 'paid'];
            case 'pay_rejected': return ['Payment request rejected', 'bad'];
            case 'failed': return ['Email failed — booking voucher not sent', 'bad'];
            case 'not_sent': return ['Booking voucher not sent yet', 'bad'];
            case 'problem': return ['Problem — see booking details', 'bad'];
            default: return [st.label || j.status, ''];
        }
    }
    function renderStays() {
        const items = ITEMS.filter(j => !j.archived_at);
        const box = document.getElementById('rows');
        if (!items.length) { box.innerHTML = `<div class="rd3-empty">No hotel stay on this tour.</div>`; return; }
        box.innerHTML = items.map(j => {
            const m = j.rd_mail, p = j.rd_pay || {}, docs = j.rd_docs || [];
            const bv = m && m.doc_id ? docs.find(d => Number(d.id) === Number(m.doc_id)) : docs.find(d => d.kind === 'booking_voucher');
            const ev = docs.filter(d => d.kind === 'confirmation');
            const versions = docs.filter(d => d.kind === 'booking_voucher');
            const rlist = (m && docs.find(d => d.kind === 'rooming_list' && Number(d.message_id) === Number(m.id))) || docs.find(d => d.kind === 'rooming_list');
            const [stage, tone] = stageWords(j);
            const sentOk = m && (m.state === 'sent' || m.state === 'recorded');
            const emailSent = sentOk ? dt(m.at) : (m && m.state === 'failed' ? 'Failed ' + dt(m.at) : '—');
            const canConfirm = ['to_book', 'requested', 'problem'].includes(j.status);
            const confirmed = ['confirmed', 'voucher_issued'].includes(j.status);
            const control = (TOUR && TOUR.tour_no ? TOUR.tour_no : '') + '-H' + String(j.day_from || 0).padStart(2, '0');
            const line = [range(j.check_in, j.check_out), roomWords(j), guestWords(j), j.meal_plan ? (MEAL[j.meal_plan] || j.meal_plan) : 'Meal plan not stated'].filter(Boolean).join(' · ');
            return `<article class="rd3-hotel">
                <div class="rd3-hotel-top"><h2 class="rd3-hname">${j.hotel_name ? esc(j.hotel_name) : 'No hotel named'}</h2>${tone === 'paid' ? '<span class="rd3-paid">Paid</span>' : ''}${CAN_MANAGE ? `<button class="td-dots" data-manage="${j.id}" title="Manage" type="button">···</button>` : ''}</div>
                <p class="rd3-hline">${esc(line)}</p>
                ${flags(j) ? `<div class="rd3-warn">${flags(j)}</div>` : ''}
                ${tone === 'paid' ? '' : `<span class="rd3-status ${tone}">${esc(stage)}</span>`}
                <div class="rd3-dates">
                    <span>Email sent: ${esc(emailSent)}</span>
                    <span>Payment approved: ${p.approved_at ? esc(dOnly(p.approved_at)) : '—'}</span>
                    <span>Paid date: ${p.paid && p.paid_date ? esc(dOnly(p.paid_date)) : '—'}</span>
                </div>
                ${m && m.state === 'failed' && !sentOk ? `<p class="rd3-bad">${esc(m.error || 'The e-mail was not sent.')}</p>` : ''}
                ${confirmed && p.link_state === 'failed' ? `<p class="rd3-bad">${esc(p.link_message || 'The payment request was not raised.')}</p>` : ''}
                <div class="rd3-btns">
                    ${bv ? `<a class="rd3-b" href="${BASE}/doc/${bv.id}" target="_blank">View Booking Voucher</a>` : '<button class="rd3-b" disabled>View Booking Voucher</button>'}
                    ${rlist ? `<a class="rd3-b" href="${BASE}/doc/${rlist.id}" target="_blank">View Rooming List</a>` : ''}
                    ${m ? `<a class="rd3-b" href="${BASE}/message/${m.id}" target="_blank">View Sent Email</a>` : '<button class="rd3-b" disabled>View Sent Email</button>'}
                    ${p.voucher_no ? `<a class="rd3-b" href="<?= base_url('accounts/voucher_print') ?>/${encodeURIComponent(p.voucher_no)}?id=${encodeURIComponent(p.voucher_no)}" target="_blank">View Payment Voucher</a>` : ''}
                    ${(!m || !sentOk) && !confirmed ? `<button class="rd3-b" data-open="${j.id}">Send booking voucher</button>` : ''}
                    ${confirmed && !p.payment_request_id && p.link_state !== 'held' ? `<button class="rd3-b" data-raise="${j.id}">Raise payment request</button>` : ''}
                    ${canConfirm ? `<button class="rd3-b primary" data-cb="${j.id}">Record Hotel Confirmation</button>` : ''}
                </div>
                <details class="rd3-fold rd3-bd"><summary>Booking details</summary>
                    <dl class="rd3-dl">
                        <div><dt>Reservation control no.</dt><dd><b>${esc(control)}</b></dd></div>
                        <div><dt>Hotel confirmation reference</dt><dd>${j.confirmation_ref ? '<b>' + esc(j.confirmation_ref) + '</b>' : '—'}</dd></div>
                        <div><dt>Confirmed by the hotel</dt><dd>${confirmed ? esc(dOnly(j.hotel_confirmed_on || j.confirmed_at)) + (j.confirmed_with ? ' · ' + esc(j.confirmed_with) : '') : '—'}</dd></div>
                        <div><dt>Recorded by</dt><dd>${confirmed ? esc(j.confirmed_by || '—') + ' · ' + esc(dt(j.confirmed_at)) : '—'}</dd></div>
                        <div><dt>Email recipient</dt><dd>${esc(m && m.to_addr ? m.to_addr : '—')}${m && m.cc_addr ? ' · CC ' + esc(m.cc_addr) : ''}</dd></div>
                        <div><dt>Email status</dt><dd>${m ? (sentOk ? (confirmed ? 'Voucher emailed — the hotel has confirmed' : 'Voucher emailed — awaiting hotel confirmation') : 'FAILED — ' + esc(m.error || '')) : 'Not sent yet'}</dd></div>
                        <div><dt>Payment request</dt><dd>${p.payment_request_id ? '#' + p.payment_request_id + (p.amount_usd ? ' · USD ' + Number(p.amount_usd).toFixed(2) : '') + (p.requested_at ? ' · ' + esc(dt(p.requested_at)) : '') : (confirmed ? esc(p.link_message || 'Not raised') : '—')}</dd></div>
                        <div><dt>Payment approved by</dt><dd>${p.approved_by ? esc(p.approved_by) : '—'}</dd></div>
                        <div><dt>Payment voucher</dt><dd>${p.voucher_no ? 'PV ' + esc(p.voucher_no) : '—'}</dd></div>
                        <div><dt>Booking voucher versions</dt><dd>${versions.length ? versions.map(d => `<a href="${BASE}/doc/${d.id}" target="_blank">v${d.version}</a> (${esc(dt(d.at))})`).join(' · ') : '—'}</dd></div>
                        <div><dt>Hotel confirmation evidence</dt><dd>${ev.length ? ev.map(d => `<a href="${BASE}/doc/${d.id}" target="_blank">${esc(d.original_name)}</a>`).join(' · ') : '—'}</dd></div>
                    </dl>
                    <div class="rd3-hist"><div class="rd3-hist-h">Email and reply history</div>
                    ${(j.rd_msgs || []).length ? (j.rd_msgs || []).map(x => {
                        const kindW = x.kind === 'reply' ? 'Hotel reply' : (x.kind === 'voucher' ? 'Hotel voucher' : 'Booking request');
                        const ok = x.state === 'sent' || x.state === 'recorded';
                        const att = [x.doc_id, x.doc2_id].filter(Boolean).map(id => docs.find(d => Number(d.id) === Number(id))).filter(Boolean);
                        return `<div class="rd3-hrow"><div><b>${esc(kindW)}</b> · ${esc(dt(x.at))}${x.by_name ? ' · ' + esc(x.by_name) : ''}</div>
                            ${x.kind === 'reply' ? `<div class="rd3-hmeta">${esc(x.reply_text || '')}</div>` : `<div class="rd3-hmeta">To ${esc(x.to_addr || '—')}${x.cc_addr ? ' · CC ' + esc(x.cc_addr) : ''} · <span class="${ok ? 'ok' : 'bad'}">${ok ? (x.channel === 'email' ? 'Sent' : 'Recorded as sent') : 'FAILED — ' + esc(x.error || '')}</span></div>
                            <div class="rd3-hmeta">Attachments: ${att.length ? att.map(d => `<a href="${BASE}/doc/${d.id}" target="_blank">${esc(d.original_name)}</a>`).join(' · ') : 'none'} · <a href="${BASE}/message/${x.id}" target="_blank">View email</a></div>`}</div>`;
                    }).join('') : '<div class="rd3-hmeta">Nothing sent or received yet.</div>'}</div>
                    <div class="rd3-btns"><button class="rd3-b" data-open="${j.id}">Full history and letters</button></div>
                </details>
            </article>`;
        }).join('');
        box.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => openCard(Number(b.dataset.open))));
        box.querySelectorAll('[data-cb]').forEach(b => b.addEventListener('click', () => openConfirmBooking(Number(b.dataset.cb))));
        box.querySelectorAll('[data-raise]').forEach(b => b.addEventListener('click', () => raiseAgain(Number(b.dataset.raise), b)));
        box.querySelectorAll('[data-manage]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const c = ITEMS.find(x => Number(x.id) === Number(b.dataset.manage)); if (c) manageMenu(b, c); }));
    }
    function openConfirmBooking(id) {
        const j = ITEMS.find(x => Number(x.id) === Number(id)); if (!j) return;
        document.getElementById('cb_id').value = id;
        document.getElementById('cb_title').textContent = 'Confirm Hotel — ' + (j.hotel_name || 'hotel');
        document.getElementById('cb_stay').textContent = `${fmtY(j.check_in)} – ${fmtY(j.check_out)} · ${j.nights} night(s) · ${j.rooms_note || ''} · ${j.meal_plan || ''}`;
        ['cb_ref', 'cb_with', 'cb_note', 'cb_reply'].forEach(k => document.getElementById(k).value = '');
        document.getElementById('cb_file').value = '';
        const t = new Date(); document.getElementById('cb_on').value = t.toISOString().slice(0, 10); document.getElementById('cb_on').max = t.toISOString().slice(0, 10);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_cb')).show();
    }
    document.getElementById('cb_ok') && document.getElementById('cb_ok').addEventListener('click', async e => {
        const btn = e.currentTarget, id = document.getElementById('cb_id').value;
        const ref = document.getElementById('cb_ref').value.trim();
        if (!ref) { toast('error', "Enter the hotel's confirmation reference."); return; }
        if (!document.getElementById('cb_reply').value.trim() && !document.getElementById('cb_file').files[0]) { toast('error', "Record the hotel's reply: paste what it wrote, or attach its e-mail / PDF."); return; }
        const fd = new FormData();
        fd.append('ref', ref); fd.append('with', document.getElementById('cb_with').value.trim()); fd.append('note', document.getElementById('cb_note').value.trim());
        fd.append('confirmed_on', document.getElementById('cb_on').value); fd.append('reply', document.getElementById('cb_reply').value.trim());
        const f = document.getElementById('cb_file').files[0]; if (f) fd.append('evidence', f);
        btn.disabled = true;
        try {
            const r = await fetch(BASE + '/card/' + id + '/confirm_booking', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd });
            const j = await r.json();
            if (j.status !== 200) { toast('error', j.message); return; }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('m_cb')).hide();
            Swal.fire({ icon: j.data && j.data.pay && j.data.pay.state === 'failed' ? 'warning' : 'success', text: j.message });
            await refreshTour();
        } finally { btn.disabled = false; }
    });
    async function raiseAgain(id, btn) {
        btn.disabled = true;
        try { const j = await post('/card/' + id + '/raise_payment', {}); Swal.fire({ icon: j.status === 200 ? 'success' : 'warning', text: j.message }); await refreshTour(); } finally { btn.disabled = false; }
    }

    // =====================================================================
    //  Activities & tickets
    // =====================================================================
    const A_STATUS = {
        to_book: ['To book', 'pill-grey'], requested: ['Requested', 'pill-blue'], confirmed: ['Confirmed', 'pill-green'],
        not_needed: ['Nothing to arrange', 'pill-teal'], problem: ['Problem', 'pill-red'], cancelled: ['Cancelled', 'pill-grey'],
        pay_at_gate: ['Pay at gate', 'pill-amber'],   // tf-tickets-r1: the entrance is paid at the counter on the day — its own status
    };
    const KIND = { entrance: 'Entrance ticket', train: 'Train ticket', safari: 'Safari', visit: 'Visit', excursion: 'Excursion', ayurveda: 'Ayurveda', lunch: 'Lunch', dinner: 'Dinner', other: 'Other' };
    const PAID = { in_tour: 'in the tour price', prepaid: 'prepaid', direct: 'paid direct by the party', unknown: 'not stated' };
    function apill(st) { const [l, c] = A_STATUS[st] || [st, 'pill-grey']; return `<span class="pill ${c}">${esc(l)}</span>`; }
    function areason(code) { const r = A_REASONS.find(x => x.code === code); return r ? r.name : (code || ''); }
    function aStatusLine(j) {
        switch (j.status) {
            case 'requested': return `sent ${when(j.requested_at)}${j.requested_at ? ' · ' + daysSince(j.requested_at) : ''}`;
            case 'confirmed': return `${j.confirmed_with === 'Ticket stock' ? '<b>' + esc(j.confirmation_ref || '') + '</b> from stock' : 'ref ' + esc(j.confirmation_ref || '')} · ${when(j.confirmed_at)}`;
            case 'not_needed': return esc(j.request_note || '');
            case 'pay_at_gate': return esc(j.request_note || 'paid at the counter on the day');
            case 'problem': return esc(j.problem_note || areason(j.problem_code));
            default: return '';
        }
    }
    function renderActs() {
        const items = A_ITEMS.filter(j => !j.archived_at);
        const tb = document.getElementById('a_rows');
        if (!items.length) { tb.innerHTML = `<tr><td colspan="6" class="empty">Nothing to arrange on this tour beyond the hotels.</td></tr>`; return; }
        tb.innerHTML = items.map(j => `<tr class="${Number(j.amended) ? 'amended' : ''}">
            <td class="mono">${j.day_no ? 'Day ' + j.day_no : '—'}<div class="help">${fmt(j.on_date)}</div></td>
            <td><b>${esc(j.title)}</b><div class="help">${esc(KIND[j.kind] || j.kind)}${j.source === 'desk' ? ' · added by the desk' : (j.source === 'excursion' ? ' · excursion on the quotation' : '')}${j.special_note ? ' — ' + esc(j.special_note) : ''}</div>${Number(j.amended) ? '<span class="flag">The quotation was amended after this line moved — check it</span>' : ''}</td>
            <td class="mono">${esc(j.pax ?? '')}${j.qty && Number(j.qty) !== Number(j.pax) ? `<div class="help">× ${esc(j.qty)}</div>` : ''}</td>
            <td class="help">${esc(PAID[j.pay_mode] || j.pay_mode || '—')}</td>
            <td>${apill(j.status)}<div class="help">${aStatusLine(j)}</div></td>
            <td style="white-space:nowrap"><button class="btn btn-light btn-sm" data-aopen="${j.id}">Open</button>${CAN_MANAGE ? ` <button class="td-dots" data-amanage="${j.id}" title="Manage" type="button">···</button>` : ''}</td></tr>`).join('');
        tb.querySelectorAll('[data-aopen]').forEach(b => b.addEventListener('click', () => openAct(Number(b.dataset.aopen))));
        tb.querySelectorAll('[data-amanage]').forEach(b => b.addEventListener('click', e => { e.stopPropagation(); const c = A_ITEMS.find(x => Number(x.id) === Number(b.dataset.amanage)); if (c) aManageMenu(b, c); }));
    }
    function aManageMenu(btn, c) {
        const items = [{ title: `${c.tour_no} · ${c.title}` }];
        if (c.archived_at) {
            items.push({ label: 'Restore to the desk', icon: 'bi-arrow-counterclockwise', hint: 'Back in the queue as it was', onClick: () => aAct2('/service_card/' + c.id + '/restore', {}) });
        } else {
            const busy = ['requested', 'confirmed'].includes(c.status);
            items.push({ label: 'Archive…', icon: 'bi-archive', hint: busy ? 'Not while the supplier holds it — back to "to book" first' : 'Off the desk, kept, restorable', disabled: busy, onClick: async () => {
                const r = await Swal.fire({ title: 'Archive this line?', text: 'Are you sure you want to archive this record? It is kept and can be restored from Manage → Archived.', input: 'text', inputPlaceholder: 'Why (optional, stays on the history)', showCancelButton: true, confirmButtonText: 'Archive' });
                if (r.isConfirmed) aAct2('/service_card/' + c.id + '/archive', { note: r.value || '' });
            } });
        }
        if (CAN_INTAKE) items.push({ sep: true });
        if (CAN_INTAKE) items.push({ label: 'Open the inquiry', icon: 'bi-box-arrow-up-right', hint: 'The quotation and its history', onClick: () => window.open(INTAKE + '?id=' + c.intake_id, '_blank') });
        window.tdMenu(btn, items);
    }
    async function aAct2(path, body) {
        const j = await manage(path, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message);
        if (ACT && j.data && Number(j.data.id) === Number(ACT.id)) { ACT = j.data; renderAct(); }
        refreshTour();
    }
    document.getElementById('a_add')?.addEventListener('click', () => {
        if (!TOUR) return;
        document.getElementById('aa_tour').value = TOUR.id;
        ['aa_what', 'aa_day', 'aa_pax', 'aa_note'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('aa_kind').value = 'other';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_addact')).show();
    });
    document.getElementById('aa_ok')?.addEventListener('click', async () => {
        const j = await post('/tour/' + document.getElementById('aa_tour').value + '/activity', { title: document.getElementById('aa_what').value, kind: document.getElementById('aa_kind').value, day_no: document.getElementById('aa_day').value, pax: document.getElementById('aa_pax').value, note: document.getElementById('aa_note').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        bootstrap.Modal.getInstance(document.getElementById('m_addact')).hide();
        toast('success', j.message); refreshTour();
    });
    async function openAct(id) {
        const j = await api('/activity/' + id);
        if (j.status !== 200) { toast('error', j.message); return; }
        ACT = j.data; renderAct();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_act')).show();
    }
    function renderAct() {
        const c = ACT, st = c.status;
        document.getElementById('a_title').textContent = `${c.tour_no} · ${c.title}`;
        // tf-tickets-r1: tickets from stock (the attraction the line's fee row is under, or one chosen), pay at gate, tickets used
        const fromStock = st === 'confirmed' && c.confirmed_with === 'Ticket stock';
        const stockAtt = c.attraction && c.attraction.control === 'stock' ? c.attraction : null;
        const gateAtt = c.attraction && c.attraction.control === 'pay_at_gate' ? c.attraction : null;
        const can = {
            request: ['to_book', 'problem'].includes(st) && !gateAtt, confirm: ['to_book', 'requested', 'problem'].includes(st) && !gateAtt,
            not_needed: ['to_book', 'requested', 'problem'].includes(st) && !stockAtt && !gateAtt, problem: ['to_book', 'requested', 'confirmed', 'not_needed', 'pay_at_gate'].includes(st),
            back: ['problem', 'requested', 'confirmed', 'not_needed', 'pay_at_gate'].includes(st),
            tickets: c.stock_on && (c.attractions || []).some(a => a.control === 'stock') && (['to_book', 'requested', 'problem'].includes(st) || fromStock) && !gateAtt,
            pay_at_gate: c.stock_on && ['to_book', 'requested', 'problem'].includes(st) && !stockAtt,
            tickets_used: fromStock && (c.tickets || []).some(t => t.status === 'allocated'),
        };
        document.getElementById('a_body').innerHTML = `
            <div class="mb-3">${window.tdStrip(c.strip, { compact: true })}</div>
            <div class="row g-3">
              <div class="col-md-7">
                <div class="box">
                  <h6>The line</h6>
                  <dl class="kv">
                    <dt>Tour</dt><dd><b>${esc(c.tour_no)}</b> · ${esc(c.tour_name || '—')} · ${esc(c.agent_name || '—')}<div class="help">${fmt(c.arrival_date)} – ${fmtY(c.departure_date)} · ${esc(c.tour_pax ?? '?')} pax</div></dd>
                    <dt>Handling agent</dt><dd>${c.handler_name ? `<b>${esc(c.handler_name)}</b>` : '<span class="pill pill-amber">nobody</span>'}${c.archived_at ? `<div class="flag" style="color:var(--tf-muted)">Archived ${when(c.archived_at)} by ${esc(c.archived_by || '')}${c.archive_note ? ' — ' + esc(c.archive_note) : ''}</div>` : ''}</dd>
                    <dt>What</dt><dd><b>${esc(c.title)}</b><div class="help">${esc(KIND[c.kind] || c.kind)}${c.source === 'desk' ? ' · added by the desk' : (c.source === 'excursion' ? ' · an excursion on the quotation' : ' · a service line on the quotation')}</div></dd>
                    <dt>When</dt><dd class="mono">${c.day_no ? 'Day ' + c.day_no : '—'}${c.on_date ? ' · ' + fmtY(c.on_date) : ''}</dd>
                    <dt>Time</dt><dd><div class="d-flex align-items-center gap-2">
                        <input type="time" class="form-control form-control-sm" style="width:130px" id="a_time" value="${esc((c.on_time || '').slice(0, 5))}">
                        <button class="btn btn-light btn-sm" id="a_savetime">Save the time</button>
                        ${c.on_time ? '<button class="btn btn-light btn-sm" id="a_cleartime" title="Take the time off this line">Clear the time</button>' : ''}</div>
                        <div class="help">The hour this line is booked for. A supplier asked to hold something needs it: a request whose wording asks for the time is held until one is entered. Leave it empty if no time has been agreed.</div></dd>
                    <dt>Party</dt><dd class="mono">${esc(c.pax ?? '—')}${c.qty ? ` <span class="help">· quantity ${esc(c.qty)}</span>` : ''}</dd>
                    <dt>Paid</dt><dd>${esc(PAID[c.pay_mode] || c.pay_mode || '—')}</dd>
                    ${c.meal ? `<dt>Meal (Meal Master)</dt><dd><b>${esc(c.meal.restaurant)}</b> · ${esc(c.meal.location)} <span class="help mono">${esc(c.meal.code)}</span>
                        <div class="help">${c.meal.time ? 'at ' + esc(c.meal.time) + ' · ' : ''}${c.meal.adults != null ? c.meal.adults + ' adult' + (c.meal.adults === 1 ? '' : 's') : ''}${c.meal.children ? ', ' + c.meal.children + ' child' + (c.meal.children === 1 ? '' : 'ren') : ''}${c.meal.total != null ? ' · ' + esc(c.meal.currency) + ' ' + Number(c.meal.total).toLocaleString(undefined, { minimumFractionDigits: 2 }) + (c.meal.tax ? ' incl. taxes ' + Number(c.meal.tax).toLocaleString(undefined, { minimumFractionDigits: 2 }) : '') : ''}</div>
                        ${c.meal.contact ? `<div class="help">${esc(c.meal.contact)}</div>` : ''}${c.meal.address ? `<div class="help">${esc(c.meal.address)}</div>` : ''}
                        <div class="help">${esc([c.meal.supplier ? 'Supplier: ' + c.meal.supplier + (c.meal.supplier_acc ? ' (' + c.meal.supplier_acc + ')' : '') : '', c.meal.cuisine, c.meal.description].filter(Boolean).join(' · '))}</div>
                        ${c.meal.outside_note ? `<div class="help" style="color:var(--tf-navy)">Outside although the hotel gives it: ${esc(c.meal.outside_note)}</div>` : ''}</dd>` : ''}
                    ${c.special_note ? `<dt>Note on the quotation</dt><dd style="white-space:pre-line">${esc(c.special_note)}</dd>` : ''}
                  </dl>
                </div>
                <div class="box">
                  <h6>Desk note</h6>
                  <textarea class="form-control form-control-sm" id="a_note" rows="2" placeholder="Anything the next person should know">${esc(c.note || '')}</textarea>
                  <div class="mt-2"><button class="btn btn-light btn-sm" id="a_savenote">Save note</button></div>
                </div>
              </div>
              <div class="col-md-5">
                <div class="box">
                  <h6>Where it stands</h6>
                  <div class="mb-2">${apill(st)}</div>
                  <dl class="kv" style="grid-template-columns:96px 1fr">
                    ${c.requested_at ? `<dt>Requested</dt><dd>${when(c.requested_at)} · ${esc(c.requested_by || '')}${c.request_note && st !== 'not_needed' ? `<div class="help">${esc(c.request_note)}</div>` : ''}</dd>` : ''}
                    ${c.confirmation_ref && !fromStock ? `<dt>Confirmed</dt><dd>ref <b>${esc(c.confirmation_ref)}</b>${c.confirmed_with ? ' with ' + esc(c.confirmed_with) : ''}<div class="help">${when(c.confirmed_at)} · ${esc(c.confirmed_by || '')}</div></dd>` : ''}
                    ${(c.tickets || []).length ? `<dt>Tickets</dt><dd><b class="mono">${esc(c.ticket_text)}</b> <span class="help">· ${esc(c.tickets[0].attraction)} · ${c.tickets.length} from stock${c.tickets.some(t => t.status === 'used') ? ' · ' + c.tickets.filter(t => t.status === 'used').length + ' used' : ''}</span><div class="help">${when(c.tickets[0].allocated_at)} · ${esc(c.tickets[0].allocated_by || '')}</div></dd>` : ''}
                    ${st === 'not_needed' ? `<dt>Nothing to arrange</dt><dd>${esc(c.request_note || '')}</dd>` : ''}
                    ${st === 'pay_at_gate' ? `<dt>Pay at gate</dt><dd>${esc(c.request_note || 'paid at the counter on the day')}</dd>` : ''}
                    ${c.stock_on && ['to_book', 'requested', 'problem'].includes(st) ? `<dt>Entrance</dt><dd class="help">${stockAtt ? `<b>${esc(stockAtt.name)}</b> is under ticket control: ${stockAtt.n_available} available${stockAtt.n_available < (c.pax || 0) ? ' — <span style="color:var(--tf-red)">fewer than the party of ' + esc(c.pax) + '</span>' : ''}` : (gateAtt ? `<b>${esc(gateAtt.name)}</b> is paid at the gate on the day` : (c.gate_hint ? 'the Entrance Fee master flags this one as paid direct at the gate — mark it so if that is right' : 'not matched to an attraction under ticket control' + (CAN_TICKETS ? ` · <a href="${TICKETS}" target="_blank">Ticket Stock</a>` : '')))}</dd>` : ''}
                    ${st === 'problem' ? `<dt>Problem</dt><dd><b>${esc(areason(c.problem_code))}</b>${c.problem_note ? `<div class="help">${esc(c.problem_note)}</div>` : ''}<div class="help">${when(c.problem_at)} · ${esc(c.problem_by || '')}</div></dd>` : ''}
                  </dl>
                  <div class="acts mt-2">
                    ${can.tickets ? `<button class="btn ${stockAtt ? 'btn-primary' : 'btn-light'} btn-sm" data-aact="tickets">${fromStock ? 'More tickets from stock' : 'Allocate tickets from stock'}</button>` : ''}
                    ${can.tickets_used ? `<button class="btn btn-light btn-sm" data-aact="tickets_used">Tickets used</button>` : ''}
                    ${can.pay_at_gate ? `<button class="btn ${gateAtt ? 'btn-primary' : 'btn-light'} btn-sm" data-aact="pay_at_gate">Pay at gate</button>` : ''}
                    ${can.request ? `<button class="btn ${stockAtt || gateAtt ? 'btn-light' : 'btn-primary'} btn-sm" data-aact="request">Request</button>` : ''}
                    ${can.confirm ? `<button class="btn btn-light btn-sm" data-aact="confirm">Record confirmation</button>` : ''}
                    ${can.not_needed ? `<button class="btn btn-light btn-sm" data-aact="not_needed">Nothing to arrange</button>` : ''}
                    ${can.problem ? `<button class="btn btn-light btn-sm" style="color:var(--tf-red)" data-aact="problem">Problem</button>` : ''}
                    ${can.back ? `<button class="btn btn-light btn-sm" data-aact="back">Back to "to book"</button>` : ''}
                  </div>
                  <div id="a_form" class="mt-2"></div>
                </div>
                ${mealBookingBox(c)}
                <div class="box">
                  <h6>Trail</h6>
                  <div class="trail">${(c.events || []).map(e => `<div><b>${esc(e.event)}</b>${e.to_status ? ` → ${esc((A_STATUS[e.to_status] || [e.to_status.replace('_', ' ')])[0].toLowerCase())}` : ''}${e.note ? ` — ${esc(e.note)}` : ''}<div class="who">${when(e.at)} · ${esc(e.by || '')}</div></div>`).join('') || '<div class="help">Nothing yet.</div>'}</div>
                </div>
              </div>
            </div>`;
        document.getElementById('a_savenote').addEventListener('click', () => aAct('note', { note: document.getElementById('a_note').value }));
        document.getElementById('a_savetime')?.addEventListener('click', () => aAct('time', { time: document.getElementById('a_time').value }));
        document.getElementById('a_cleartime')?.addEventListener('click', async () => {
            const go = await Swal.fire({ icon: 'warning', title: 'Clear the time', text: 'Take the time off this line? A request whose wording asks for the time is held until one is entered again.', showCancelButton: true, confirmButtonText: 'Clear the time' });
            if (!go.isConfirmed) return;
            document.getElementById('a_time').value = '';
            aAct('time', { time: '' });
        });
        document.querySelectorAll('#a_body [data-aact]').forEach(b => b.addEventListener('click', () => aForm(b.dataset.aact)));
        // schema 40: booking the restaurant
        document.getElementById('mv_issue_send')?.addEventListener('click', () => mealSendForm(null));
        document.getElementById('mv_issue')?.addEventListener('click', () => aAct('meal_voucher', {}));
        document.querySelectorAll('#a_body [data-mvsend]').forEach(b => b.addEventListener('click', () => mealSendForm(Number(b.dataset.mvsend))));
        document.querySelectorAll('#a_body [data-mvother]').forEach(b => b.addEventListener('click', () => {
            const f = document.getElementById('mv_form');
            f.innerHTML = `<input class="form-control form-control-sm mb-1" id="mv_how" placeholder="How it reached the restaurant (required) — handed over, WhatsApp to …"><button class="btn btn-primary btn-sm" id="mv_ogo">Record as sent</button>`;
            document.getElementById('mv_ogo').addEventListener('click', () => aAct('meal_voucher_sent', { voucher_id: b.dataset.mvother, note: document.getElementById('mv_how').value }));
        }));
        document.querySelectorAll('#a_body [data-mvcancel]').forEach(b => b.addEventListener('click', () => {
            const f = document.getElementById('mv_form');
            f.innerHTML = `<input class="form-control form-control-sm mb-1" id="mv_why" placeholder="Why voucher ${esc(b.dataset.mvno)} is cancelled (required — it stays on the trail)"><button class="btn btn-primary btn-sm" id="mv_cgo">Cancel the voucher</button>`;
            document.getElementById('mv_cgo').addEventListener('click', () => aAct('meal_voucher_cancel', { voucher_id: b.dataset.mvcancel, why: document.getElementById('mv_why').value }));
        }));
        if (c.archived_at) { document.querySelectorAll('#a_body .acts .btn, #a_savenote, #a_note, #a_time, #a_savetime, #a_cleartime').forEach(el => { el.disabled = true; }); }
    }
    // schema 40: the restaurant of a Meal Master meal is booked with a booking voucher (the amount in LKR from the Meal Master),
    // emailed when the tour is confirmed; here the desk sees where it went and sends, records or cancels what is left
    const MV_STATE = { sent: ['emailed', 'pill-green'], other: ['sent another way', 'pill-green'], failed: ['not sent — failed', 'pill-red'], not_sent: ['not sent yet', 'pill-amber'] };
    function mealBookingBox(c) {
        const b = c.meal_booking;
        if (!c.meal || !b) return '';
        const n2 = v => Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const vs = c.meal_vouchers || [];
        const live = vs.find(v => v.state === 'issued');
        const closed = ['cancelled', 'not_needed'].includes(c.status) || c.archived_at;
        const list = vs.map(v => { const [st, pc] = MV_STATE[v.mail_status] || [v.mail_status, 'pill-grey'];
            return `<div class="mb-2"${v.state === 'cancelled' ? ' style="opacity:.6"' : ''} data-mv="${v.id}"><b class="mono">${esc(v.voucher_no)}</b> · <b>LKR ${n2(v.amount_lkr)}</b>
            · ${v.state === 'cancelled' ? '<span class="pill pill-grey">cancelled</span>' + (v.cancel_note ? ` <span class="help">${esc(v.cancel_note)}</span>` : '') : `<span class="pill ${pc}">${esc(st)}</span>`}
            <div class="help">${v.mail_status === 'sent' ? 'to ' + esc(v.to_email) + ' · ' + when(v.sent_at) + ' · ' + esc(v.sent_by || '') : (v.mail_status === 'other' ? esc(v.note || '') + ' · ' + when(v.sent_at) : (v.mail_error ? esc(v.mail_error) : (v.to_email ? 'to ' + esc(v.to_email) : 'no booking email')))}</div>
            <div class="help">issued ${when(v.issued_at)} · ${esc(v.issued_by || '')}</div>
            <div class="d-flex gap-1 mt-1 flex-wrap"><a class="btn btn-light btn-sm" href="${BASE}/meal_voucher/${v.id}" target="_blank"><i class="bi bi-printer"></i> View / print</a>${v.state === 'issued' && !c.archived_at ? `<button class="btn btn-light btn-sm" data-mvsend="${v.id}">${v.mail_status === 'sent' ? 'Send again' : 'Send'}</button><button class="btn btn-light btn-sm" data-mvother="${v.id}">Sent another way</button><button class="btn btn-light btn-sm" data-mvcancel="${v.id}" data-mvno="${esc(v.voucher_no)}">Cancel</button>` : ''}</div></div>`; }).join('');
        const block = b.why ? `<div class="flag">${esc(b.why)}</div>` : (b.stale ? `<div class="flag">The meal on the tour has changed since ${esc(live ? live.voucher_no : 'the voucher')} was issued — cancel it with the reason, then issue a fresh one.</div>` : '');
        return `<div class="box" id="meal_book">
            <h6>Booking voucher</h6>
            <div class="mb-1">${b.amount_lkr != null ? `<b>LKR ${n2(b.amount_lkr)}</b> <span class="help">· ${esc(b.lkr_text || '')}</span>` : '<span class="help">no LKR amount yet</span>'}</div>
            <div class="help mb-1">${b.to ? 'Booking email: ' + esc(b.to) : '<span style="color:var(--tf-amber)">No booking email for the restaurant</span>'}${b.contact ? ' · ' + esc(b.contact) : ''}${b.needs_supplier ? ' · <span style="color:var(--tf-amber)">not linked to a supplier account yet</span>' : ''}</div>
            ${block}
            ${list}
            ${!live && !closed && !b.why ? `<div class="d-flex gap-1 mt-1 flex-wrap"><button class="btn btn-primary btn-sm" id="mv_issue_send">Issue and email the voucher</button><button class="btn btn-light btn-sm" id="mv_issue">Issue only</button></div>` : ''}
            <div id="mv_form" class="mt-2"></div>
        </div>`;
    }
    function mealSendForm(voucherId) {
        const c = ACT, m = c.meal_mail || {}, st = m.start || { subject: '', message: '' };
        const f = document.getElementById('mv_form');
        const opts = (m.templates || []).map((t, i) => `<option value="${i}" ${t.id === st.id ? 'selected' : ''}>${esc(t.name)}</option>`).join('');
        f.innerHTML = `<div class="p-2" style="border:1px solid var(--tf-line);border-radius:8px">
            ${m.can_send ? '' : '<div class="flag mb-1">Your account has no mailbox set up to send from — print the voucher and record it as sent another way, or ask Management to set the mailbox up.</div>'}
            <label class="form-label help mb-0">To</label><input class="form-control form-control-sm mb-1" id="mv_to" value="${esc(c.meal_booking.to || '')}" placeholder="the restaurant's booking email">
            ${m.can_save_master ? '<div class="form-check mb-1"><input class="form-check-input" type="checkbox" id="mv_savem"><label class="form-check-label help" for="mv_savem">Save this address on the Meal Master</label></div>' : ''}
            <label class="form-label help mb-0">Cc</label><input class="form-control form-control-sm mb-1" id="mv_cc" placeholder="optional">
            ${opts ? `<label class="form-label help mb-0">Ready-made email</label><select class="form-select form-select-sm mb-1" id="mv_tpl">${opts}</select>` : ''}
            <label class="form-label help mb-0">Subject</label><input class="form-control form-control-sm mb-1" id="mv_subj" value="${esc(st.subject || '')}">
            <label class="form-label help mb-0">Message <span class="text-muted">(the voucher follows it, and goes as a PDF too)</span></label><textarea class="form-control form-control-sm mb-1" id="mv_msg" rows="5">${esc(st.message || '')}</textarea>
            <button class="btn btn-primary btn-sm" id="mv_go" ${m.can_send ? '' : 'disabled'}>${voucherId ? 'Send the voucher' : 'Issue and send'}</button>
            ${m.from ? `<span class="help ms-1">from ${esc(m.from)}</span>` : ''}</div>`;
        document.getElementById('mv_tpl')?.addEventListener('change', e => { const t = (m.templates || [])[Number(e.target.value)]; if (t) { document.getElementById('mv_subj').value = t.subject; document.getElementById('mv_msg').value = t.message; } });
        document.getElementById('mv_go').addEventListener('click', () => {
            const body = { to: document.getElementById('mv_to').value, cc: document.getElementById('mv_cc').value, subject: document.getElementById('mv_subj').value, message: document.getElementById('mv_msg').value,
                           save_master: document.getElementById('mv_savem')?.checked ? 1 : 0 };
            if (voucherId) aAct('meal_voucher_send', Object.assign(body, { voucher_id: voucherId }));
            else aAct('meal_voucher', Object.assign(body, { send: 1 }));
        });
    }
    function aForm(kind) {
        const f = document.getElementById('a_form');
        const reasons = A_REASONS.map(r => `<option value="${esc(r.code)}">${esc(r.name)}</option>`).join('');
        const forms = {
            request: `<div class="help mb-1">Ask the supplier as you do today — email, phone, the counter; this records that it went.</div><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Note (optional)"><button class="btn btn-primary btn-sm" id="af_go">Mark as requested</button>`,
            confirm: `<input class="form-control form-control-sm mb-1" id="af_ref" placeholder="Confirmation or ticket reference"><input class="form-control form-control-sm mb-1" id="af_with" placeholder="Confirmed with (name at the supplier, optional)"><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Note (optional)"><button class="btn btn-primary btn-sm" id="af_go">Record confirmation</button>`,
            not_needed: `<div class="help mb-1">Nothing to arrange — paid at the gate, no booking taken, already in hand. Say which; it stays on the trail.</div><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Why nothing needs arranging (required)"><button class="btn btn-primary btn-sm" id="af_go">Mark: nothing to arrange</button>`,
            problem: `<select class="form-select form-select-sm mb-1" id="af_code">${reasons}</select><input class="form-control form-control-sm mb-1" id="af_note" placeholder="What happened (needed for Other)"><button class="btn btn-primary btn-sm" id="af_go">Mark as a problem</button><div class="help mt-1">It shows on the Tour Board and on the inquiry so the Quotation Desk can offer the agent an alternative.</div>`,
            back: `<input class="form-control form-control-sm mb-1" id="af_note" placeholder="What changed (required)"><button class="btn btn-primary btn-sm" id="af_go">Back to "to book"</button>${(ACT.tickets || []).some(t => t.status === 'allocated') ? '<div class="help mt-1">The tickets allocated to this line go back to stock with it.</div>' : ''}`,
            // tf-tickets-r1
            tickets: `<div class="help mb-1">The lowest available numbers of the attraction, one per passenger — never a number another tour holds. The numbers go on the card, the Board, the record and the driver's itinerary copy.</div>${(ACT.tickets || []).length ? '' : `<select class="form-select form-select-sm mb-1" id="af_att">${(ACT.attractions || []).filter(a => a.control === 'stock').map(a => `<option value="${a.id}" ${ACT.attraction && ACT.attraction.id === a.id ? 'selected' : ''}>${esc(a.name)} · ${a.n_available} available</option>`).join('')}</select>`}<input class="form-control form-control-sm mb-1" id="af_count" type="number" min="1" value="${(ACT.tickets || []).length ? '' : esc(ACT.pax ?? '')}" placeholder="${(ACT.tickets || []).length ? 'How many more' : 'How many (the party)'}"><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Note (optional)"><button class="btn btn-primary btn-sm" id="af_go">${(ACT.tickets || []).length ? 'Allocate more' : 'Allocate'}</button>`,
            tickets_used: `<div class="help mb-1">The tour has run and the tickets were handed in at the gate. They read "used" on Ticket Stock; the line stays confirmed.</div><button class="btn btn-primary btn-sm" id="af_go">Mark the tickets used</button>`,
            pay_at_gate: `<div class="help mb-1">The entrance is paid at the counter on the day${ACT.gate_amount ? ' — ' + esc(ACT.gate_amount) : ''}. Its own status: counted as arranged, and the driver's itinerary copy says "pay at gate".</div><input class="form-control form-control-sm mb-1" id="af_note" placeholder="Note (optional — who pays, from which float)"><button class="btn btn-primary btn-sm" id="af_go">Mark: pay at gate</button>`,
        };
        f.innerHTML = forms[kind] || '';
        document.getElementById('af_go')?.addEventListener('click', () => aAct(kind, {
            note: document.getElementById('af_note')?.value, ref: document.getElementById('af_ref')?.value,
            with: document.getElementById('af_with')?.value, code: document.getElementById('af_code')?.value,
            attraction_id: document.getElementById('af_att')?.value, count: document.getElementById('af_count')?.value,
        }));
    }
    async function aAct(kind, body) {
        const j = await post('/activity/' + ACT.id + '/' + kind, body);
        if (j.status !== 200) { toast('error', j.message); if (j.data && j.data.id) { ACT = j.data; renderAct(); } return; }
        ACT = j.data; renderAct(); toast('success', j.message); refreshTour();
    }

    // ---------- the ⋯ Manage menu: correct, archive, restore (Management; the server checks again) ----------
    const manage = (path, body) => fetch(MANAGE_BASE + path, { method:'POST', headers:{ 'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest' }, body: JSON.stringify(body || {}) }).then(r => r.json());
    // The signed-in person's password, checked on the server before an action that cannot be taken back (cancel,
    // delete). Returns the password to send with the action, or null when the person stepped back or the check
    // failed — nothing is changed on a failure, and the wrong attempt is on the Activity log.
    async function askPassword(title, text, purpose) {
        const pw = await Swal.fire({ title: title + ' — your password', html: `<div style="text-align:left;font-size:13.5px">${esc(text)}</div>`, input: 'password', inputPlaceholder: 'Your password', inputAttributes: { autocomplete: 'current-password' }, inputValidator: v => (v ? undefined : 'Enter your password.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#b23a32' });
        if (!pw.isConfirmed) return null;
        const a = await manage('/reauth', { password: pw.value || '', purpose: purpose || title });
        if (a.status !== 200 || !a.ok) { await Swal.fire({ icon: 'error', title: 'Not done', text: a.message || 'Password not recognised. Nothing was changed.' }); return null; }
        return pw.value || '';
    }
    const ME_NAME = <?= json_encode((string) ($me_name ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    // the ⋯ on a tour row (Admin only; the server checks the privilege again): cancel or delete the whole tour
    function tourManageMenu(btn, t) {
        const live = t.stage !== 'cancelled';
        const items = [{ title: `${t.tour_no} · ${t.tour_name || ''}` }];
        items.push({ label: 'Open on Admin', icon: 'bi-shield-lock', hint: 'Every tour, cancel, delete, restore', onClick: () => window.open(MANAGE_BASE, '_blank') });
        items.push({ sep: true });
        items.push({ label: live ? 'Cancel tour…' : 'Reopen tour…', icon: live ? 'bi-x-circle' : 'bi-arrow-counterclockwise', hint: live ? 'Management: password, reason, final warning; every card marked; off both counts' : 'Management: password, reason, final warning; back on the desks, cards at their first step', onClick: async () => {
            if (live) {
                // Management only, in three steps: the password, the reason, the final warning — then every card is marked
                const pw = await askPassword('Cancel tour ' + t.tour_no, 'Cancelling marks every hotel and transport card cancelled and takes the tour off both desks\' counts. Enter your own sign-in password to go on; it is checked and not kept.', 'Cancel tour ' + t.tour_no);
                if (pw === null) return;
                const why = await Swal.fire({ title: 'Why is ' + t.tour_no + ' cancelled?', input: 'text', inputPlaceholder: 'The reason (required, stays on the trail and on every card)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#b23a32' });
                if (!why.isConfirmed) return;
                const fin = await Swal.fire({ icon: 'warning', title: 'Cancel tour ' + t.tour_no + '?', html: `<div style="text-align:left;font-size:13.5px"><div style="background:#fbe9e6;border:1px solid #f0b8b0;border-radius:8px;padding:8px 12px;margin-bottom:10px;color:#8a2a22"><b>Last warning.</b> Every hotel, activity and transport card is marked <b>Cancelled</b> for the desks with your reason, the tour leaves the live board and counts for nothing on either desk. Work already done stays on the trail. Reopen brings it back only at the cards' first step.</div>Reason: <b>${esc(why.value.trim())}</b><br>Cancelled by <b>${esc(ME_NAME)}</b>, now; the name and time go on the tour and the Activity log.</div>`, showCancelButton: true, cancelButtonText: 'Keep the tour', confirmButtonText: 'Cancel the tour', confirmButtonColor: '#b23a32', focusCancel: true });
                if (!fin.isConfirmed) return;
                const j = await manage('/tour/' + t.id + '/cancel', { why: why.value.trim(), password: pw });
                if (j.status !== 200) { toast('error', j.message); return; }
                toast('success', j.message); loadTours();
                return;
            }
            // Reopen undoes a cancellation: Management only, in the same three steps — the password, the reason, the last warning
            const pw = await askPassword('Reopen tour ' + t.tour_no, 'Reopening undoes the cancellation: the tour returns to the live board and to both desks at the cards\' first step. Enter your own sign-in password to go on; it is checked and not kept.', 'Reopen tour ' + t.tour_no);
            if (pw === null) return;
            const why = await Swal.fire({ title: 'Why is ' + t.tour_no + ' reopened?', input: 'text', inputPlaceholder: 'The reason (required, stays on the trail)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Continue', confirmButtonColor: '#1f7a4d' });
            if (!why.isConfirmed) return;
            const fin = await Swal.fire({ icon: 'warning', title: 'Reopen tour ' + t.tour_no + '?', html: `<div style="text-align:left;font-size:13.5px"><div style="background:#fbe9e6;border:1px solid #f0b8b0;border-radius:8px;padding:8px 12px;margin-bottom:10px;color:#8a2a22"><b>Last warning.</b> The cancellation is undone. The tour is back on the live board and on Hotel Bookings and Transport, every card at its first step; the cancellation and this reopen stay on the trail.</div>Reason: <b>${esc(why.value.trim())}</b><br>Reopened by <b>${esc(ME_NAME)}</b>, now; the name, the time and the reason go on the Activity log.</div>`, showCancelButton: true, cancelButtonText: 'Leave it cancelled', confirmButtonText: 'Reopen the tour', confirmButtonColor: '#1f7a4d', focusCancel: true });
            if (!fin.isConfirmed) return;
            const j = await manage('/tour/' + t.id + '/reopen', { why: why.value.trim(), password: pw });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); loadTours();
        } });
        items.push({ label: 'Delete tour…', icon: 'bi-trash', danger: true, hint: 'Cancelled and archived with its inquiry; off every list and count; restorable from Admin', onClick: async () => {
            const pw = await askPassword('Delete tour ' + t.tour_no, 'Deleting cancels the tour and archives it with its inquiry. Enter your own sign-in password to go on; it is checked and not kept.', 'Delete tour ' + t.tour_no);
            if (pw === null) return;
            const r = await Swal.fire({ title: 'Delete tour ' + t.tour_no + '?', html: '<div style="text-align:left;font-size:13.5px">Are you sure you want to delete this record? The tour is cancelled and archived with its inquiry: off Inquiries, Hotel Bookings, Transport, the Board and every red count. Nothing is removed — it is kept whole under Admin → Deleted / archived and can be restored from there.</div>', input: 'text', inputPlaceholder: 'Why (required, stays on the trail)', inputValidator: v => (v && v.trim() ? undefined : 'The reason is required.'), showCancelButton: true, confirmButtonText: 'Delete the tour', confirmButtonColor: '#b23a32' });
            if (!r.isConfirmed) return;
            const j = await manage('/tour/' + t.id + '/delete', { why: r.value || '', password: pw });
            if (j.status !== 200) { toast('error', j.message); return; }
            toast('success', j.message); loadTours();
        } });
        window.tdMenu(btn, items);
    }
    function manageMenu(btn, c) {
        const items = [{ title: `${c.tour_no} · ${c.hotel_name || 'no hotel named'}` }];
        if (c.archived_at) {
            items.push({ label: 'Restore to the desk', icon: 'bi-arrow-counterclockwise', hint: 'Back in the queue as it was', onClick: () => act2('/hotel_card/' + c.id + '/restore', {}) });
        } else {
            items.push({ label: 'Correct this stay…', icon: 'bi-pencil-square', hint: 'Hotel, dates, meal plan, rooms — logged', onClick: () => openEdit(c) });
            items.push({ sep: true });
            const busy = ['requested', 'confirmed', 'voucher_issued'].includes(c.status);
            items.push({ label: 'Archive…', icon: 'bi-archive', hint: busy ? 'Not while the hotel holds it — back to "pending" first' : 'Off the desk, kept, restorable', disabled: busy, onClick: async () => {
                const r = await Swal.fire({ title: 'Archive this stay?', text: 'Are you sure you want to archive this record? It is kept and can be restored from Manage → Archived.', input: 'text', inputPlaceholder: 'Why (optional, stays on the history)', showCancelButton: true, confirmButtonText: 'Archive' });
                if (r.isConfirmed) act2('/hotel_card/' + c.id + '/archive', { note: r.value || '' });
            } });
        }
        if (CAN_INTAKE) items.push({ sep: true });
        if (CAN_INTAKE) items.push({ label: 'Open the inquiry', icon: 'bi-box-arrow-up-right', hint: 'The quotation and its history', onClick: () => window.open(INTAKE + '?id=' + c.intake_id, '_blank') });
        window.tdMenu(btn, items);
    }
    async function act2(path, body) {
        const j = await manage(path, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        toast('success', j.message);
        if (CARD && j.data && Number(j.data.id) === Number(CARD.id)) { const keep = CARD.letter; CARD = j.data; CARD.letter = CARD.letter || keep; renderCard(); }
        refreshTour();
    }
    function openEdit(c) {
        document.getElementById('e_id').value = c.id;
        document.getElementById('e_title').textContent = `Correct ${c.tour_no} · ${c.hotel_name || 'stay'}`;
        document.getElementById('e_hotel').value = c.hotel_name || '';
        document.getElementById('e_area').value = c.area || '';
        document.getElementById('e_in').value = c.check_in || '';
        document.getElementById('e_out').value = c.check_out || '';
        document.getElementById('e_meal').value = c.meal_plan || '';
        document.getElementById('e_rooms').value = c.rooms ?? '';
        document.getElementById('e_rooms_note').value = c.rooms_note || '';
        document.getElementById('e_pax').value = c.pax ?? '';
        document.getElementById('e_special').value = c.special_note || '';
        document.getElementById('e_note').value = '';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_edit')).show();
    }
    document.getElementById('e_ok')?.addEventListener('click', async () => {
        const id = document.getElementById('e_id').value;
        const fields = { hotel_name: document.getElementById('e_hotel').value, area: document.getElementById('e_area').value, check_in: document.getElementById('e_in').value, check_out: document.getElementById('e_out').value,
            meal_plan: document.getElementById('e_meal').value, rooms: document.getElementById('e_rooms').value, rooms_note: document.getElementById('e_rooms_note').value, pax: document.getElementById('e_pax').value, special_note: document.getElementById('e_special').value };
        const j = await manage('/hotel_card/' + id + '/edit', { fields, note: document.getElementById('e_note').value });
        if (j.status !== 200) { toast('error', j.message); return; }
        bootstrap.Modal.getInstance(document.getElementById('m_edit')).hide();
        toast('success', j.message);
        if (CARD && Number(CARD.id) === Number(id)) { const keep = CARD.letter; CARD = j.data; CARD.letter = CARD.letter || keep; renderCard(); }
        refreshTour();
    });
    function statusLine(j) {
        switch (j.status) {
            case 'requested': return `sent ${when(j.requested_at)}${j.requested_at ? ' · ' + daysSince(j.requested_at) : ''}`;
            case 'confirmed': return `ref ${esc(j.confirmation_ref || '')} · ${when(j.confirmed_at)} · voucher to issue`;
            case 'voucher_issued': return `${esc(j.voucher_no || '')} · ${when(j.voucher_at)}`;
            case 'problem': return esc(j.problem_note || reasonName(j.problem_code));
            default: return '';
        }
    }
    function daysSince(s) { const d = Math.floor((Date.now() - new Date(String(s).replace(' ', 'T')).getTime()) / 86400000); return d <= 0 ? 'today' : `waiting ${d} day${d === 1 ? '' : 's'}`; }
    function reasonName(code) { const r = REASONS.find(x => x.code === code); return r ? r.name : (code || ''); }

    // =====================================================================
    //  The stay: its card, the letters to the hotel, the hotel's answers
    // =====================================================================
    async function openCard(id) {
        const j = await api('/card/' + id);
        if (j.status !== 200) { toast('error', j.message); return; }
        CARD = j.data; renderCard();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_card')).show();
    }
    async function loadDetails(tourId, boxId) {
        const box = document.getElementById(boxId);
        if (!box) return;
        const j = await api('/tour/' + tourId + '/details');
        if (j.status !== 200) { box.innerHTML = `<div class="help">${esc(j.message || 'The tour details could not be read.')}</div>`; return; }
        DETAILS[tourId] = j.data;
        box.innerHTML = window.tdDetails(j.data, { print: BASE + '/tour/' + tourId + '/programme' });
    }
    const MSG_WORD = { request: 'Request', voucher: 'Voucher', reply: 'Hotel replied' };
    function renderCard() {
        const c = CARD;
        document.getElementById('c_title').textContent = `${c.tour_no} · ${c.hotel_name || 'Hotel not named'} · ${fmt(c.check_in)} → ${fmt(c.check_out)}`;
        const st = c.status;
        const can = {
            request: ['to_book', 'problem'].includes(st) && !Number(c.needs_hotel) && !Number(c.needs_meal),
            confirm: ['to_book', 'requested', 'problem'].includes(st),
            voucher: st === 'confirmed',
            problem: ['to_book', 'requested', 'confirmed', 'voucher_issued'].includes(st),
            back: ['problem', 'requested', 'confirmed', 'voucher_issued'].includes(st),
        };
        const rate = SEES_RATES && c.rate ? (c.rate.available === false && !c.rate.totals
            ? `<div class="help">${esc(c.rate.note || 'No published rate covers these nights.')}</div>`
            : `<div class="help">Published contract: ${esc((c.rate.plans || []).join(', '))}${c.rate.missing && c.rate.missing.length ? ` · <span class="pill pill-amber">${c.rate.missing.length} night(s) not covered</span>` : ''}</div>
               <table class="tbl mt-1"><thead><tr><th>Room type</th><th>Meal</th><th>Single</th><th>Double</th><th>Triple</th></tr></thead><tbody>
               ${(c.rate.totals || []).map(t => `<tr><td>${esc(t.room_type)}</td><td>${esc(t.meal_plan)}</td><td class="mono">${t.sgl ?? '—'}</td><td class="mono">${t.dbl ?? '—'}</td><td class="mono">${t.tpl ?? '—'}</td></tr>`).join('') || '<tr><td colspan="5" class="empty">No complete rate for the whole stay.</td></tr>'}
               </tbody></table><div class="help mt-1">${esc(c.rate.currency || '')} for the whole stay of ${c.rate.nights} night(s). Shown for reference — the quotation is not changed here.</div>`) : '';
        const letter = c.letter || {};
        const kindVoucher = st === 'voucher_issued';
        const canLetter = !Number(c.needs_hotel) && !Number(c.needs_meal) && st !== 'cancelled' && !c.archived_at;
        const msgs = (c.messages || []).map(m => `<div class="msg"><span class="k ${esc(m.kind)} ${m.state === 'failed' ? 'failed' : ''}">${esc(MSG_WORD[m.kind] || m.kind)}${m.state === 'failed' ? ' — not sent' : (m.channel === 'other' ? ' — by other means' : '')}</span> ${when(m.at)} · ${esc(m.by_name || m.by || '')}${m.to_addr ? ` · to ${esc(m.to_addr)}` : ''}${m.error ? `<div class="help" style="color:var(--tf-red)">${esc(m.error)}</div>` : ''}${m.kind === 'reply' ? `<div style="white-space:pre-line">${esc(m.body || '')}</div>` : (m.body ? ` <a href="#" data-msg="${m.id}">view</a>` : '')}</div>`).join('');

        document.getElementById('c_body').innerHTML = `
            ${c.strip ? `<div class="mb-3">${window.tdStrip(c.strip, { compact: true })}</div>` : ''}
            <div class="row g-3">
              <div class="col-lg-7">
                <div class="box">
                  <h6>The stay</h6>
                  <dl class="kv">
                    <dt>Tour</dt><dd><b>${esc(c.tour_no)}</b> · ${esc(c.tour_name || '—')} · ${esc(c.agent_name || '—')}<div class="help">${fmt(c.arrival_date)} – ${fmtY(c.departure_date)} · ${esc(c.tour_pax ?? '?')} pax${c.hotel_class ? ' · ' + esc(c.hotel_class) : ''}</div></dd>
                    <dt>Handling agent</dt><dd>${c.handler_name ? `<b>${esc(c.handler_name)}</b> <span class="help">· whose tour it is</span>` : '<span class="pill pill-amber">nobody</span>'}${c.archived_at ? `<div class="flag" style="color:var(--tf-muted)">Archived ${when(c.archived_at)} by ${esc(c.archived_by || '')}${c.archive_note ? ' — ' + esc(c.archive_note) : ''}</div>` : ''}</dd>
                    <dt>Hotel</dt><dd>${c.hotel_name ? `<b>${esc(c.hotel_name)}</b>` : '<span class="pill pill-red">not named</span>'} ${c.area ? `<span class="help">· ${esc(c.area)}</span>` : ''}${c.hotel_acc ? `<div class="help mono">${esc(c.hotel_acc)}</div>` : ''}${letter.hotel_email || letter.hotel_phone ? `<div class="help">${letter.hotel_email ? esc(letter.hotel_email) : ''}${letter.hotel_email && letter.hotel_phone ? ' · ' : ''}${letter.hotel_phone ? esc(letter.hotel_phone) : ''}</div>` : ''}${flags(c)}</dd>
                    <dt>Nights</dt><dd class="mono">${fmtY(c.check_in)} → ${fmtY(c.check_out)} · ${c.nights} night${Number(c.nights) === 1 ? '' : 's'} <span class="help">(nights ${c.day_from}${c.day_to !== c.day_from ? '–' + c.day_to : ''} of the tour)</span></dd>
                    <dt>Rooms</dt><dd>${c.rooms ? c.rooms + ' room' + (Number(c.rooms) === 1 ? '' : 's') : '—'}${c.rooms_note ? ' · ' + esc(c.rooms_note) : ''}</dd>
                    ${Object.prototype.hasOwnProperty.call(c, 'room_type_text') ? `<dt>Room type</dt><dd><span id="c_rt_word">${c.room_type_text ? `<b>${esc(c.room_type_text)}</b>` : '<span class="help">as the master names it on the rooms line</span>'}</span>${c.archived_at ? '' : ` <button class="btn btn-light btn-sm py-0" id="c_rt_edit" type="button" title="The room type as it should read on this booking — this stay only; the Hotel Master is not changed">change</button><span id="c_rt_form" style="display:none"> <input class="form-control form-control-sm d-inline-block" style="width:220px" id="c_rt_text" maxlength="120" value="${esc(c.room_type_text || '')}" placeholder="e.g. Deluxe Lake View"> <button class="btn btn-primary btn-sm py-0" id="c_rt_save" type="button">Save</button></span>`}</dd>` : ''}
                    <dt>Meal plan</dt><dd>${c.meal_plan ? esc(c.meal_plan) : '<span class="pill pill-amber">not stated</span>'}</dd>
                    <dt>Party</dt><dd class="mono">${esc(c.pax ?? '—')}</dd>
                    ${c.crew_rooms ? `<dt>Crew rooms</dt><dd>${esc(c.crew_rooms)}</dd>` : ''}
                    ${c.special_note ? `<dt>Special requests</dt><dd style="white-space:pre-line">${esc(c.special_note)}</dd>` : ''}
                  </dl>
                </div>
                <div class="box">
                  <h6>${kindVoucher ? 'Voucher to the hotel' : 'Booking request to the hotel'}</h6>
                  ${canLetter ? `
                  <div class="row g-2">
                    <div class="col-md-7"><label class="form-label help">To${letter.to_source === 'stay' ? ' <span class="text-muted">(as the last letter on this stay)</span>' : (letter.to_source === 'master' ? ' <span class="text-muted">(the hotel\'s Reservations e-mail, from the Hotel Master)</span>' : (letter.to_source === 'supplier' ? ' <span class="text-muted">(from the supplier record)</span>' : ''))}</label><input class="form-control form-control-sm" id="l_to" value="${esc(letter.to || '')}" placeholder="${letter.to ? '' : 'No Reservations e-mail on the Hotel Master — type it once; the next letter on this stay keeps it'}">
                      ${letter.to_source === 'stay' && letter.master_email && letter.master_email !== letter.to ? `<div class="help">The Hotel Master has ${esc(letter.master_email)}; this stay uses the address typed for it.</div>` : ''}
                      ${letter.can_master && !letter.master_email ? `<div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="l_save_master"><label class="form-check-label help" for="l_save_master">Also save this address on the Hotel Master as the hotel's Reservations e-mail (for every future booking)</label></div>` : ''}</div>
                    <div class="col-md-5"><label class="form-label help">Copy to</label><input class="form-control form-control-sm" id="l_cc" value="${esc(letter.cc || '')}" placeholder="optional"></div>
                    ${(letter.templates || []).length ? `<div class="col-12"><label class="form-label help">Template <span class="text-muted">(fills the subject and the opening; edit them as you like)</span></label><select class="form-select form-select-sm" id="l_template">${(() => { let g = null, out = ''; (letter.templates || []).forEach(t => { if (t.category_label !== g) { if (g !== null) out += '</optgroup>'; g = t.category_label; out += `<optgroup label="${esc(g)}">`; } out += `<option value="${t.id}" ${Number(t.id) === Number(letter.template_id) ? 'selected' : ''}>${esc(t.name)}${t.is_default ? ' (default)' : ''}</option>`; }); if (g !== null) out += '</optgroup>'; return out; })()}</select></div>` : ''}
                    <div class="col-12"><label class="form-label help">Subject</label><input class="form-control form-control-sm" id="l_subject" value="${esc(letter.subject || '')}"></div>
                    <div class="col-12"><label class="form-label help">Opening of the email <span class="text-muted">(the greeting and the covering lines above the booking details; blank = the standard lines)</span></label><textarea class="form-control form-control-sm" id="l_message" rows="4" maxlength="4000">${esc(letter.message || '')}</textarea></div>
                    <div class="col-12"><label class="form-label help">Remarks for this letter <span class="text-muted">(the agent's requests above go on it already)</span></label><input class="form-control form-control-sm" id="l_remarks" maxlength="500" placeholder="e.g. early check-in requested, connecting rooms if possible"></div>
                    ${SEES_RATES && !kindVoucher && c.rate && (c.rate.totals || []).length ? `<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="l_rate" checked><label class="form-check-label help" for="l_rate">Quote the published contract rate on the request</label></div></div>` : ''}
                  </div>
                  <div class="acts mt-2">
                    <button class="btn btn-light btn-sm" id="l_preview"><i class="bi bi-eye"></i> Preview</button>
                    <button class="btn btn-primary btn-sm" id="l_send" ${letter.can_send ? '' : 'title="Your account has no mailbox set up to send from"'}><i class="bi bi-send"></i> Send by email</button>
                    <a class="btn btn-light btn-sm" id="l_print" href="#" target="_blank"><i class="bi bi-printer"></i> Print / PDF</a>
                    <button class="btn btn-light btn-sm" id="l_sent" title="The request went another way — WhatsApp, the hotel's portal, the phone"><i class="bi bi-check2"></i> Sent another way…</button>
                  </div>
                  <div class="help mt-2">${letter.can_send ? `Goes from your own mailbox (${esc(letter.sender_email || '')}), as the Hotel Reservation screen sends; the copy sent is kept here.` : 'Your account has no mailbox set up to send from (email, password and host on your profile) — print the letter, or record that it went another way.'}</div>`
                  : `<div class="help">${c.archived_at ? 'This card is archived.' : (st === 'cancelled' ? 'This tour is cancelled.' : 'The letter cannot go until the Quotation Desk fills in what is flagged above (the hotel, the meal plan).')}</div>`}
                </div>
                <div class="box">
                  <h6>Letters and replies</h6>
                  <div>${msgs || '<div class="help">Nothing sent or received yet.</div>'}</div>
                  ${!c.archived_at && st !== 'cancelled' ? `<div class="mt-2"><textarea class="form-control form-control-sm" id="l_reply" rows="2" placeholder="What the hotel said — waitlisted, asked for a deposit, offered another room type… (kept with who and when)"></textarea><div class="mt-1"><button class="btn btn-light btn-sm" id="l_reply_go">Record the hotel's reply</button></div></div>` : ''}
                </div>
                ${rate ? `<div class="box"><h6>Contract rate</h6>${rate}</div>` : ''}
                <div class="box">
                  <h6>Desk note</h6>
                  <textarea class="form-control form-control-sm" id="c_note" rows="2" placeholder="Anything the next person should know">${esc(c.note || '')}</textarea>
                  <div class="mt-2"><button class="btn btn-light btn-sm" id="c_savenote">Save note</button></div>
                </div>
              </div>
              <div class="col-lg-5">
                <div class="box">
                  <h6>Where it stands</h6>
                  <div class="mb-2">${pill(c)}</div>
                  <dl class="kv" style="grid-template-columns:96px 1fr">
                    ${c.requested_at ? `<dt>Sent</dt><dd>${when(c.requested_at)} · ${esc(c.requested_by || '')}${c.request_note ? `<div class="help">${esc(c.request_note)}</div>` : ''}</dd>` : ''}
                    ${c.confirmation_ref ? `<dt>Confirmed</dt><dd>ref <b>${esc(c.confirmation_ref)}</b>${c.confirmed_with ? ' with ' + esc(c.confirmed_with) : ''}<div class="help">${when(c.confirmed_at)} · ${esc(c.confirmed_by || '')}</div></dd>` : ''}
                    ${c.voucher_no ? `<dt>Voucher</dt><dd><b>${esc(c.voucher_no)}</b><div class="help">${when(c.voucher_at)} · ${esc(c.voucher_by || '')}</div></dd>` : ''}
                    ${st === 'problem' ? `<dt>Problem</dt><dd><b>${esc(reasonName(c.problem_code))}</b>${c.problem_note ? `<div class="help">${esc(c.problem_note)}</div>` : ''}<div class="help">${when(c.problem_at)} · ${esc(c.problem_by || '')}</div></dd>` : ''}
                  </dl>
                  <div class="acts mt-2">
                    ${can.request ? `<button class="btn btn-light btn-sm" data-act="request" title="The request went another way; this records that">Mark as sent</button>` : ''}
                    ${can.confirm ? `<button class="btn btn-primary btn-sm" data-act="confirm">Record confirmation</button>` : ''}
                    ${can.voucher ? `<button class="btn btn-success btn-sm" data-act="voucher">Issue voucher</button>` : ''}
                    ${can.problem ? `<button class="btn btn-light btn-sm" style="color:var(--tf-red)" data-act="problem">Rejected / problem</button>` : ''}
                    ${can.back ? `<button class="btn btn-light btn-sm" data-act="back">Back to pending</button>` : ''}
                  </div>
                  <div id="c_form" class="mt-2"></div>
                  ${(Number(c.needs_hotel) || Number(c.needs_meal)) && ['to_book', 'problem'].includes(st) ? `<div class="help mt-2">This stay cannot be requested until the Quotation Desk fills in what is flagged above.</div>` : ''}
                </div>
                <div class="box">
                  <h6>Trail</h6>
                  <div class="trail">${(c.events || []).map(e => `<div><b>${esc(e.event)}</b>${e.to_status ? ` → ${esc((STATUS[e.to_status] || [e.to_status.replace('_', ' ')])[0].toLowerCase())}` : ''}${e.note ? ` — ${esc(e.note)}` : ''}<div class="who">${when(e.at)} · ${esc(e.by || '')}</div></div>`).join('') || '<div class="help">Nothing yet.</div>'}</div>
                  ${(c.activity || []).length ? `<h6 class="mt-3">Corrections</h6>${window.tdHistory((c.activity || []).map(a => Object.assign({ lane: 'hotel' }, a)), {})}` : ''}
                </div>
              </div>
            </div>`;

        document.getElementById('c_savenote').addEventListener('click', () => act('note', { note: document.getElementById('c_note').value }));
        // tf-walkfix-r1: the room type as this booking describes it — changed here for this stay only, never on the master
        document.getElementById('c_rt_edit')?.addEventListener('click', () => { const f = document.getElementById('c_rt_form'); f.style.display = f.style.display === 'none' ? '' : 'none'; if (f.style.display !== 'none') document.getElementById('c_rt_text').focus(); });
        document.getElementById('c_rt_save')?.addEventListener('click', () => act('room_type', { text: document.getElementById('c_rt_text').value }));
        document.querySelectorAll('#c_body [data-act]').forEach(b => b.addEventListener('click', () => form(b.dataset.act)));
        document.querySelectorAll('#c_body [data-msg]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); showMessage(Number(a.dataset.msg)); }));
        document.getElementById('l_reply_go')?.addEventListener('click', () => act('reply', { text: document.getElementById('l_reply').value }));
        if (canLetter) {
            const kind = kindVoucher ? 'voucher' : 'request';
            const qs = () => new URLSearchParams({ rate: document.getElementById('l_rate') && document.getElementById('l_rate').checked ? '1' : '0', remarks: document.getElementById('l_remarks').value || '', message: document.getElementById('l_message').value || '' }).toString();
            document.getElementById('l_message').addEventListener('input', () => { document.getElementById('l_print').href = printUrl(); });
            document.getElementById('l_template')?.addEventListener('change', e => {
                const t = (letter.templates || []).find(x => Number(x.id) === Number(e.target.value));
                if (!t) return;
                document.getElementById('l_subject').value = t.subject; document.getElementById('l_message').value = t.message;
                document.getElementById('l_print').href = printUrl();
                toast('success', 'Template "' + t.name + '" filled in the subject and the opening; edit them as you like.');
            });
            const printUrl = () => `${BASE}/card/${c.id}/document/${kind}?${qs()}`;
            document.getElementById('l_print').href = printUrl();
            document.getElementById('l_remarks').addEventListener('input', () => { document.getElementById('l_print').href = printUrl(); });
            document.getElementById('l_rate')?.addEventListener('change', () => { document.getElementById('l_print').href = printUrl(); });
            document.getElementById('l_preview').addEventListener('click', async () => {
                const r = await fetch(`${BASE}/card/${c.id}/document/${kind}?format=email&${qs()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await r.text();
                document.getElementById('g_title').textContent = document.getElementById('l_subject').value || 'Preview';
                document.getElementById('g_body').innerHTML = `<div class="help mb-2">To ${esc(document.getElementById('l_to').value || '—')}${document.getElementById('l_cc').value ? ' · copy ' + esc(document.getElementById('l_cc').value) : ''}</div>${html}`;
                bootstrap.Modal.getOrCreateInstance(document.getElementById('m_msg')).show();
            });
            document.getElementById('l_send').addEventListener('click', async () => {
                const to = document.getElementById('l_to').value.trim();
                if (!to) { toast('error', 'Enter the hotel\'s email address.'); return; }
                const r = await Swal.fire({ title: (kindVoucher ? 'Send the voucher' : 'Send the booking request') + ' to ' + to + '?', text: 'It goes from your own mailbox; the copy sent is kept on the stay.', icon: 'question', showCancelButton: true, confirmButtonText: 'Send' });
                if (!r.isConfirmed) return;
                await act('send', { kind, to, cc: document.getElementById('l_cc').value, subject: document.getElementById('l_subject').value, remarks: document.getElementById('l_remarks').value, message: document.getElementById('l_message').value, rate: document.getElementById('l_rate') && document.getElementById('l_rate').checked ? 1 : 0, save_master: document.getElementById('l_save_master') && document.getElementById('l_save_master').checked ? 1 : 0 });
            });
            document.getElementById('l_sent').addEventListener('click', async () => {
                const r = await Swal.fire({ title: (kindVoucher ? 'Voucher' : 'Request') + ' sent another way?', input: 'text', inputPlaceholder: 'How — WhatsApp, the hotel\'s portal, by phone (kept on the trail)', showCancelButton: true, confirmButtonText: 'Record it' });
                if (!r.isConfirmed) return;
                await act('sent', { kind, note: r.value || '' });
            });
        }
        if (c.archived_at) { document.querySelectorAll('#c_body .acts .btn, #c_savenote, #c_note').forEach(el => { el.disabled = true; }); }
    }
    function showMessage(id) {
        const m = (CARD.messages || []).find(x => Number(x.id) === id);
        if (!m) return;
        document.getElementById('g_title').textContent = `${MSG_WORD[m.kind] || m.kind} · ${when(m.at)} · ${m.by_name || m.by || ''}`;
        document.getElementById('g_body').innerHTML = `<div class="help mb-2">To ${esc(m.to_addr || '—')}${m.cc_addr ? ' · copy ' + esc(m.cc_addr) : ''} · ${esc(m.subject || '')}${m.state === 'failed' ? ` · <span class="pill pill-red">not sent — ${esc(m.error || '')}</span>` : ''}</div>${m.body || ''}`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('m_msg')).show();
    }

    function form(kind) {
        const f = document.getElementById('c_form');
        const reasons = REASONS.map(r => `<option value="${esc(r.code)}">${esc(r.name)}</option>`).join('');
        const forms = {
            request: `<div class="help mb-1">The request went to the hotel another way; this records that it went. To send it from here, use the booking request box.</div><input class="form-control form-control-sm mb-1" id="f_note" placeholder="How it went (optional)"><button class="btn btn-primary btn-sm" id="f_go">Mark as sent</button>`,
            confirm: `<input class="form-control form-control-sm mb-1" id="f_ref" placeholder="Hotel's confirmation reference"><input class="form-control form-control-sm mb-1" id="f_with" placeholder="Confirmed with (name at the hotel, optional)"><input class="form-control form-control-sm mb-1" id="f_note" placeholder="Note (optional)"><button class="btn btn-primary btn-sm" id="f_go">Record confirmation</button>`,
            voucher: `<div class="help mb-1">A voucher number is taken from the counter and put on the card; the voucher can then be printed or sent from the box on the left.</div><input class="form-control form-control-sm mb-1" id="f_note" placeholder="Note (optional)"><button class="btn btn-success btn-sm" id="f_go">Issue voucher</button>`,
            problem: `<select class="form-select form-select-sm mb-1" id="f_code">${reasons}</select><input class="form-control form-control-sm mb-1" id="f_note" placeholder="What the hotel said (needed for Other)"><button class="btn btn-primary btn-sm" id="f_go">Record it</button><div class="help mt-1">Rejected / unavailable, alternative required, or any other problem: it shows on the Tour Board and on the inquiry so the Quotation Desk can offer the agent an alternative. The quotation itself is not changed here.</div>`,
            back: `<input class="form-control form-control-sm mb-1" id="f_note" placeholder="What changed (required)"><button class="btn btn-primary btn-sm" id="f_go">Back to pending</button>`,
        };
        f.innerHTML = forms[kind] || '';
        document.getElementById('f_go')?.addEventListener('click', () => act(kind, {
            note: document.getElementById('f_note')?.value, ref: document.getElementById('f_ref')?.value,
            with: document.getElementById('f_with')?.value, code: document.getElementById('f_code')?.value,
        }));
    }

    async function act(kind, body) {
        const j = await post('/card/' + CARD.id + '/' + kind, body);
        if (j.status !== 200) { toast('error', j.message); return; }
        CARD = j.data; renderCard(); toast('success', j.message); refreshTour();
    }

    document.getElementById('f_q').addEventListener('input', () => { clearTimeout(window.__hq); window.__hq = setTimeout(() => { PAGE = 1; loadTours(); }, 250); });
    ['f_from', 'f_to'].forEach(id => { const el = document.getElementById(id); const t = el.parentElement.querySelector('.rdl-dtxt');
        el.addEventListener('change', () => { t.textContent = el.value ? fmtY(el.value) : t.dataset.empty; el.parentElement.classList.toggle('set', !!el.value); PAGE = 1; loadTours(); });
        el.parentElement.addEventListener('click', e => { if (e.target !== el) { e.preventDefault(); try { el.showPicker(); } catch (x) { el.focus(); } } }); });
    document.getElementById('f_clear').addEventListener('click', () => { ['f_q', 'f_from', 'f_to'].forEach(id => document.getElementById(id).value = ''); PAGE = 1; loadTours(); });
    if (window.TfView) TfView.restoreList(false);   // tf-refresh-r1: this tab's search and filters
    const P0 = new URLSearchParams(location.search);
    if (P0.get('handler')) document.getElementById('f_handler').value = P0.get('handler');
    loadTours();
    // tf-refresh-r1: opened from the address (a refresh): the same tour, read fresh, and the place this tab was at
    if (P0.get('tour')) openTour(Number(P0.get('tour')), true).then(() => { if (window.TfView) TfView.restoreScroll(); });
    window.addEventListener('popstate', () => {
        const id = Number(new URLSearchParams(location.search).get('tour') || 0);
        if (id) { openTour(id, true); }
        else if (TOUR) { backToList.fromHistory = true; try { backToList(); } finally { backToList.fromHistory = false; } }
    });
})();
</script>
</body>
</html>
